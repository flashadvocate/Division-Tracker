<?php

class MemberController
{

    public static function _profile($id)
    {

        $user = User::find(intval($_SESSION['userid']));
        $member = Member::find(intval($_SESSION['memberid']));
        $tools = Tool::find_all($user->role);
        $divisions = Division::find_all();
        $extrajs = array();
        // profile data
        $memberInfo = Member::findByMemberId(intval($id));

        if (property_exists($memberInfo, 'id')) {

            $divisionInfo = Division::findById(intval($memberInfo->game_id));
            $platoonInfo = Platoon::findById(intval($memberInfo->platoon_id));
            $recruits = Member::findRecruits($memberInfo->member_id);
            $gamesPlayed = MemberGame::get($memberInfo->id);
            $aliases = MemberHandle::findByMemberId($memberInfo->id);

            // game data
            $bdate = date("Y-m-d", strtotime("tomorrow - 30 days"));
            $edate = date("Y-m-d", strtotime("tomorrow"));
            $totalGames = BfActivity::countPlayerGames($memberInfo->member_id, $bdate, $edate);
            $aodGames = BfActivity::countPlayerAODGames($memberInfo->member_id, $bdate, $edate);
            $games = BfActivity::find_allGames($memberInfo->member_id);
            $pctAod = ($totalGames > 0) ? $aodGames * 100 / $totalGames : 0;

            switch ($divisionInfo->short_name) {
                case "ps2":
                    $handle_info = MemberHandle::findHandle($memberInfo->id, 11);

                    if (empty($handle_info->handle_value)) {
                        $handle = $memberInfo->forum_name;
                    } else {
                        $handle = $handle_info->handle_value;
                    }
                    $activity = array(
                        'ps2_character_name' => $handle
                    );
                    $extrajs[] = "libraries/angular.min";
                    $extrajs[] = "libraries/angular-chart.min";
                    $extrajs[] = "ps2/controllers";
                    $activity_page = $divisionInfo->short_name;
                    break;
                default:
                    $activity = array();
                    $activity_page = 'default';
                    break;
            }

            if (property_exists($platoonInfo, 'id')) {
                $platoonInfo->link = "<li><a href='divisions/{$divisionInfo->short_name}/platoon/{$platoonInfo->number}'>{$platoonInfo->name}</a></li>";
                $platoonInfo->item = "<li class='list-group-item text-right'><span class='pull-left'><strong>Platoon: </strong></span> <span class='text-muted'>{$platoonInfo->name}</span></li>";
            }

            // if squad leader, show recruits
            if ($memberInfo->position_id == 5) {
                Flight::render('member/sl-personnel', array('member' => $memberInfo), 'sl_personnel');
            }

            Flight::render('member/alerts', array('memberInfo' => $memberInfo), 'alerts');
            Flight::render('member/recruits', array('recruits' => $recruits), 'recruits');
            Flight::render('member/member_data', array(
                'memberInfo' => $memberInfo,
                'divisionInfo' => $divisionInfo,
                'platoonInfo' => $platoonInfo,
                'aliases' => $aliases
            ), 'member_data');
            Flight::render('member/activity/' . $activity_page, $activity, 'activity');
            Flight::render('member/history', array(), 'history');
            Flight::render('member/profile', array(
                'user' => $user,
                'member' => $member,
                'memberInfo' => $memberInfo,
                'divisionInfo' => $divisionInfo,
                'platoonInfo' => $platoonInfo
            ), 'content');
            Flight::render('layouts/application', array(
                'js' => 'member',
                'user' => $user,
                'member' => $member,
                'tools' => $tools,
                'divisions' => $divisions,
                'extrajs' => $extrajs
            ));

        } else {
            Flight::redirect('/404', 404);
        }

    }

    public static function _edit()
    {

        $user = User::find(intval($_SESSION['userid']));
        $member = Member::findByMemberId($_POST['member_id']);
        $platoons = Platoon::find_all($member->game_id);

        // if user role lower than plt ld, show only own platoon's squads
        $platoon_id = (($user->role >= 2) && (!User::isDev())) ? $member->platoon_id : false;
        $squads = Squad::findAll($member->game_id, $platoon_id);

        $positionsArray = Position::find_all();
        $rolesArray = Role::find_all();
        $memberGames = MemberGame::get($member->id);

        if (User::isUser($member->id)) {
            $userInfo = User::findByMemberId($member->id);
        } else {
            $userInfo = null;
        }

        Flight::render('modals/view_member', array(
            'user' => $user,
            'member' => $member,
            'userInfo' => $userInfo,
            'platoons' => $platoons,
            'memberGames' => $memberGames,
            'squads' => $squads,
            'positionsArray' => $positionsArray,
            'rolesArray' => $rolesArray
        ));

    }

    public static function _doUpdateMember()
    {

        // user attempting to make changes
        $respUser = User::find(intval($_SESSION['userid']));
        $respMember = Member::find(intval($_SESSION['memberid']));

        // member being changed
        $memberData = $_POST['memberData'];
        $member = Member::findByMemberId($memberData['member_id']);
        $user = User::findByMemberId(Member::findId($memberData['member_id']));

        // only update values allowed by role
        if (!User::isDev()) {
            if ($respUser->role < 2) {
                unset($memberData['squad_id'], $memberData['position_id'], $memberData['platoon_id']);
            }
            if ($respUser->role < 3) {
                unset($memberData['platoon_id']);
            }
        }

        // only continue if we have permission to edit the user
        if (User::canEdit($memberData['member_id'], $respUser, $member) == true) {

            // don't log if user edits their own profile
            if ($respMember->member_id != $member->member_id) {
                UserAction::create(array(
                    'type_id' => 3,
                    'date' => date("Y-m-d H:i:s"),
                    'user_id' => $respMember->member_id,
                    'target_id' => $member->member_id
                ));
            }

            // validate recruiter
            if ($memberData['recruiter'] != 0 && !Member::exists($memberData['recruiter'])) {
                $data = array('success' => false, 'message' => "Recruiter id is invalid.");

                // validate squad leader / squad_id setting
            } else {
                if ($respMember->position_id < 5 && $memberData['position_id'] == 5 && $memberData['squad_id'] != 0) {
                    $data = array('success' => false, 'message' => "Squad leaders cannot be in a squad.");
                } else {

                    // update member info
                    Member::modify($memberData);
                }
            }

            // update games
            if (isset($_POST['played_games'])) {
                $games = $_POST['played_games'];
                foreach ($games as $game) {
                    $params = new stdClass();
                    $params->member_id = $member->id;
                    $params->game_id = $game;
                    MemberGame::add($params);
                }
            }

            // update user
            if (isset($_POST['userData'])) {
                $userData = $_POST['userData'];

                // wish I had a better way to do this... yuck
                $userData['developer'] = (isset($userData['developer'])) ? $userData['developer'] : 0;

                if (!User::isDev()) {
                    unset($userData['developer']);
                }

                if ($respMember->member_id != $member->member_id && $user->role >= $respUser->role && !User::isDev()) {
                    $data = array('success' => false, 'message' => "You are not authorized to make that change.");
                } else {
                    User::modify($userData);
                }
            }

            // update aliases
            if (isset($_POST['userAliases'])) {
                $aliases = $_POST['userAliases'];

                foreach ($aliases as $type => $value) {

                    $type = Handle::findByName($type)->id;

                    if ($value != '') {

                        $params = array(
                            'member_id' => $memberData['id'],
                            'handle_type' => $type,
                            'handle_value' => trim($value),
                            'handle_account_id' => '0',
                            'invalid' => '0',
                            'invalid_date' => '0000-00-00'
                        );
                        $id = MemberHandle::hasAlias($type, $memberData['id']);

                        if ($id) {

                            $params['id'] = $id;
                            MemberHandle::modify($params);

                        } else {
                            MemberHandle::add($params);
                        }

                    }
                }
            }

        } else {
            $data = array('success' => false, 'message' => 'You do not have permission to modify this player.');
        }

        if (!isset($data['success'])) {
            $data = array('success' => true, 'message' => "Member information updated!");
        }

        // print out a pretty response
        echo(json_encode($data));
    }

    public static function _doValidateMember()
    {
        $member_id = $_POST['member_id'];

        if (Member::exists($member_id)) {
            $data = array('success' => false, 'memberExists' => true);
        } else {
                $data = array('success' => true);
        }
        echo(json_encode($data));
    }

    public static function _doAddMember()
    {

        $user = User::find(intval($_SESSION['userid']));
        $member = Member::find(intval($_SESSION['memberid']));
        $division = Division::findById($member->game_id);

        $platoon_id = ($user->role >= 3 || User::isDev()) ? $_POST['platoon_id'] : $member->platoon_id;
        $squad_id = ($user->role >= 2 || User::isDev()) ? $_POST['squad_id'] : (Squad::mySquadId($member->id)) ?: 0;
        $recruiter = $member->member_id;

        $position_id = 6;

        // provide params for brand new members
        $params = array(
            'member_id' => $_POST['member_id'],
            'forum_name' => trim($_POST['forum_name']),
            'recruiter' => $recruiter,
            'game_id' => $_POST['game_id'],
            'status_id' => 999,
            'join_date' => date("Y-m-d H:i:s"),
            'last_forum_login' => date("Y-m-d H:i:s"),
            'last_activity' => date("Y-m-d H:i:s"),
            'last_forum_post' => date("Y-m-d H:i:s"),
            'last_promotion' => date("Y-m-d H:i:s"),
            'rank_id' => 1,
            'platoon_id' => $platoon_id,
            'squad_id' => $squad_id,
            'position_id' => $position_id
        );

        if (Member::exists($_POST['member_id'])) {

            // update existing record
            $existing_member_id = Member::findId($_POST['member_id']);
            $params = array_merge($params, array('id' => $existing_member_id));

            $affected_rows = Member::modify($params);

            if ($affected_rows > 0) {
                UserAction::create(array(
                    'type_id' => 10,
                    'date' => date("Y-m-d H:i:s"),
                    'user_id' => $member->member_id,
                    'target_id' => $params['member_id']
                ));

                $data = array('success' => true, 'message' => "Existing member successfully updated!");
            } else {
                $data = array('success' => false, 'message' => "Existing member could not be updated.");
            }

        } else {
            // member doesn't exist
            $insert_id = Member::create($params);

            if ($insert_id != 0) {

                UserAction::create(array(
                    'type_id' => 1,
                    'date' => date("Y-m-d H:i:s"),
                    'user_id' => $member->member_id,
                    'target_id' => $params['member_id']
                ));

                // temporary recruiting notifications for battlefront
                if ($_POST['game_id'] == 4) {
                    $slack = new Slack;
                    $message = $member->forum_name . " just recruited " . $params['forum_name'] . "! :thumbsup:";
                    $slack->message($message)->send();
                }

                $data = array('success' => true, 'message' => "Member successfully added!");
            } else {
                $data = array('success' => false, 'message' => "Member could not be added.");
            }

        }

        if (isset($insert_id) && $insert_id != 0) {
            if (isset($_POST['played_games'])) {
                $games = $_POST['played_games'];
                foreach ($games as $game) {
                    $memberGame = new stdClass();
                    $memberGame->member_id = $insert_id;
                    $memberGame->game_id = $game;
                    MemberGame::add($memberGame);
                }
            }

            if (isset($_POST['ingame_name'])) {
                $ingame_name = trim($_POST['ingame_name']);
                $handle = new stdClass();
                $handle->member_id = $insert_id;
                $handle->handle_type = $division->primary_handle;
                $handle->handle_value = $ingame_name;
                $handle->handle_account_id = '0';
                $handle->invalid = '0';
                $handle->invalid_date = '0000-00-00';
                MemberHandle::add($handle);
            }
        }

        if (empty($data['success'])) {
            $data = array('success' => false, 'message' => "Something went wrong. This incident has been logged.");
        }

        echo(json_encode($data));
    }

    public static function _doUpdateFlag()
    {

        $action = $_POST['action'];
        $member_flagged = $_POST['id'];
        $flagged_by = $_POST['member_id'];

        if ($action == 1) {
            $params = new stdClass();
            $params->member_flagged = $member_flagged;
            $params->flagged_by = $flagged_by;
            InactiveFlagged::add($params);

            $data = array('success' => true, 'message' => 'Member {$member_flagged} flagged for removal.');
            UserAction::create(array(
                'type_id' => 4,
                'date' => date("Y-m-d H:i:s"),
                'user_id' => $flagged_by,
                'target_id' => $member_flagged
            ));

        } else {

            InactiveFlagged::remove($member_flagged);
            $data = array('success' => true, 'message' => 'Member {$member_flagged} no longer flagged for removal.');
            UserAction::create(array(
                'type_id' => 6,
                'date' => date("Y-m-d H:i:s"),
                'user_id' => $flagged_by,
                'target_id' => $member_flagged
            ));
        }

        echo(json_encode($data));
    }

    public static function _doAssignMemberToPlatoon()
    {
        $member = $_POST['member_id'];
        $platoon = $_POST['platoon_id'];
        Member::assignToPlatoon(compact('member', 'platoon'));
        echo json_encode(['success' => true]);
    }

    public static function _doKickFromAod()
    {
        $user = Member::find($_SESSION['memberid']);
        $target = Member::find(['member_id' => $_POST['id']]);

        Member::kickFromAod($target->member_id);

        UserAction::create(array(
            'type_id' => 2,
            'date' => date("Y-m-d H:i:s"),
            'user_id' => $user->member_id,
            'target_id' => $target->member_id
        ));

    }

}
