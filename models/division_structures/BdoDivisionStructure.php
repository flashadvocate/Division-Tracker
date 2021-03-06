<?php

/**
 * BF Division Structure
 *
 * Generates a bb-code template with prepopulated member data
 *
 */
class BdoDivisionStructure
{
    public function __construct($game_id)
    {
        $this->game_id = $game_id;

        // get data
        $this->division = Division::findById($this->game_id);
        $this->platoons = Platoon::find_all($this->game_id);

        // colors
        $this->division_leaders_color = "#00FF00";
        $this->platoon_leaders_color = "#00FF00";
        $this->squad_leaders_color = "#FFA500";
        $this->div_name_color = "#FF0000";
        $this->platoon_num_color = "#FF0000";
        $this->platoon_pos_color = "#40E0D0";

        // number of columns
        $this->num_columns = 4;

        // widths
        $this->players_width = 900;
        $this->info_width = 800;

        // misc settings
        $this->min_num_squad_leaders = 2;

        self::generate();
    }

    private function getDivisionLeaders($division_structure)
    {
        $division_leaders = Division::findDivisionLeaders($this->game_id);
        foreach ($division_leaders as $division_leader) {
            $aod_url = Member::createAODlink([
                'member_id' => $division_leader->member_id,
                'rank' => Rank::convert($division_leader->rank_id)->abbr,
                'forum_name' => $division_leader->forum_name,
            ]);
            $division_structure .= (property_exists($division_leader,
                'position_desc')) ? "{$aod_url} - {$division_leader->position_desc}\r\n" : "{$aod_url}\r\n";
        }
        return $division_structure;
    }


    public function generate()
    {

        // header
        $division_structure = "[table='align:center,width: {$this->info_width}']";
        $division_structure .= "[tr][td]";

        // banner
        $division_structure .= "[center][img]http://i.imgur.com/n460d57.png[/img]
        [size=5][color=#fff]NA Server*[/color][/size]
        [size=2]*Check TS channel header for server channel[/size][/center]\r\n";

        /**
         * ------division leaders-----
         */

        // division leaders
        $division_structure .= "\r\n\r\n[center][size=5][color={$this->division_leaders_color}][b][i][u]Division Leadership[/u][/i][/b][/color][/size]\r\n";
        $division_structure .= "[size=4]";
        $division_structure = $this->getDivisionLeaders($division_structure);
        $division_structure .= "[/size][/center]\r\n\r\n";

        /**
         * -----general sergeants-----
         */

        $division_structure .= "[center][size=3][color={$this->platoon_pos_color}]General Sergeants[/color]\r\n";
        $general_sergeants = Division::findGeneralSergeants($this->game_id);
        foreach ($general_sergeants as $player) {
            $memberHandle = MemberHandle::findHandle($player->id, $this->division->primary_handle);

            $player->handle = (is_object($memberHandle))
                ? $memberHandle->handle_value
                : 'XXX';

            $player_name = Rank::convert($player->rank_id)->abbr." ".$player->forum_name;
            $aod_url = Member::createAODlink(array('member_id'=>$player->member_id, 'forum_name'=>$player_name));

            $bl_url = "[size=1]IGF: {$player->handle}[/size]";
            $division_structure .= "{$aod_url} \r\n{$bl_url}\r\n\r\n";
        }

        $division_structure .= "[/size][/center]";
        $division_structure .= "[/td][/tr][/table]";

        /**
         * ---------platoons----------
         */

        $division_structure .= "\r\n\r\n[table='align:center,width: {$this->players_width}']";
        $platoons = $this->platoons;
        $i = 1;

        foreach ($platoons as $platoon) {
            $countMembers = Platoon::countPlatoon($platoon->id);

            if ($i == 1) {
                $division_structure .= "[tr]";
                $division_structure .= "[td]";
            } else {
                $division_structure .= "[td]";
            }

            // platoon leader
            $player = Member::findByMemberId($platoon->leader_id);

            // is a platoon leader assigned?
            if ($platoon->leader_id != 0) {
                $memberHandle = MemberHandle::findHandle($player->id, $this->division->primary_handle);
                $player->handle = (is_object($memberHandle))
                    ? $memberHandle->handle_value
                    : 'XXX';

                $player_name = Rank::convert($player->rank_id)->abbr." ".$player->forum_name;
                $aod_url = Member::createAODlink(array(
                    'member_id' => $player->member_id,
                    'forum_name' => $player_name
                ));

                $bl_url = "[size=1]IGF: {$player->handle}[/size]";

                $division_structure .= "[size=3][color={$this->platoon_pos_color}]Platoon Leader[/color][/size]\r\n{$aod_url}\r\n{$bl_url}\r\n\r\n";
            } else {
                $division_structure .= "[size=3][color={$this->platoon_pos_color}]Platoon Leader[/color]\r\n[color={$this->platoon_leaders_color}]TBA[/color][/size]\r\n\r\n";
            }

            // squad leaders
            $squads = Squad::findAll($this->game_id, $platoon->id);

            foreach ($squads as $squad) {
                if ($squad->leader_id != 0) {
                    $squad_leader = Member::findById($squad->leader_id);

                    $memberHandle = MemberHandle::findHandle($squad_leader->id, $this->division->primary_handle);

                    $player_name = Rank::convert($squad_leader->rank_id)->abbr." ".$squad_leader->forum_name;

                    $aod_url = Member::createAODlink(array('member_id'=>$squad_leader->member_id, 'forum_name'=>$player_name, 'color'=>$this->squad_leaders_color));

                    if (is_object($memberHandle)) {
                        $bl_url = "[size=1]IGF: {$memberHandle->handle_value}[/size]";
                    } else {
                        $bl_url = '[color=#ff0000]XXX[/color]';
                    }

                    $division_structure .= "[size=3][color={$this->platoon_pos_color}]Squad Leader[/color][/size]\r\n{$aod_url}\r\n{$bl_url}\r\n\r\n";

                    // direct recruits
                    $recruits = arrayToObject(Member::findRecruits($squad_leader->member_id, $squad_leader->platoon_id, $squad->id, true));

                    if (count((array) $recruits)) {
                        $division_structure .= "[list=1]";

                        foreach ($recruits as $player) {
                            $memberHandle = MemberHandle::findHandle($player->id, $this->division->primary_handle);
                            $player_name = Rank::convert($player->rank_id)->abbr." ".$player->forum_name;
                            $aod_url = Member::createAODlink(['member_id'=>$player->member_id, 'forum_name'=>$player_name]);

                            // does member have a division primary member handle?
                            if (count(( array ) $memberHandle)) {
                                $bl_url = "IGF: {$memberHandle->handle_value}";
                                $division_structure .= "[*]{$aod_url}\r\n{$bl_url}\r\n\r\n";
                            } else {
                                $division_structure .= "[*]{$aod_url} [color=red]XX[/color]\r\n";
                            }
                        }

                        $division_structure .= "[/list]";
                    }
                } else {
                    $division_structure .= "[size=3][color={$this->platoon_pos_color}]Squad Leader[/color]\r\n[color={$this->squad_leaders_color}]TBA[/color][/size]\r\n";
                }

                $division_structure .= "\r\n";

                // squad members
                $squadMembers = arrayToObject(
                    Squad::findSquadMembers(
                        $squad->id,
                        true,
                        (isset($squad_leader)) ? $squad_leader->member_id : null
                    )
                );

                if (count((array) $squadMembers)) {
                    foreach ($squadMembers as $player) {

                        $memberHandle = MemberHandle::findHandle($player->id, $this->division->primary_handle);
                            $player->handle = (is_object($memberHandle))
                                ? $memberHandle->handle_value
                                : 'XXX';

                            $player_name = Rank::convert($player->rank_id)->abbr." ".$player->forum_name;
                            $aod_url = Member::createAODlink(array(
                                'member_id' => $player->member_id,
                                'forum_name' => $player_name
                            ));

                            $bl_url = "[size=1]IGF: {$player->handle}[/size]";
                            $division_structure .= "{$aod_url}\r\n{$bl_url}\r\n\r\n";

                    }
                }

                $division_structure .= "\r\n";
            }

            $division_structure .= "\r\n\r\n";

            if ($i % $this->num_columns == 0) {
                $division_structure .= "[/td][/tr][tr]";
            }
            $division_structure .= "[/td]";

            $i++;
        }

        // end last platoon
        $division_structure .= "[/tr][/table]\r\n\r\n";

        /**
         * --------part timers--------
         */

        $i = 1;

        // header
        $division_structure .= "\r\n[table='align:center,width: {$this->info_width}']";
        $division_structure .= "[tr][td]\r\n[center][size=3][color={$this->platoon_pos_color}][b]Part Time Members[/b][/color][/size][/center][/td][/tr]";
        $division_structure .= "[/table]\r\n\r\n";

        // players
        $division_structure .= "[table='align:center,width: {$this->info_width}']";
        $division_structure .= "[tr][td]";

        $partTimers = PartTime::find_all($this->game_id);

        foreach ($partTimers as $player) {
            if ($i % 20 == 0) {
                $division_structure .= "[/td][td]";
            }
            $aod_url = Member::createAODlink([
                'member_id' => $player->member_id,
                'forum_name'=> "AOD_" . $player->forum_name
            ]);
            $division_structure .= "{$aod_url}\r\nIGF: {$player->ingame_alias}\r\n\r\n";
            $i++;
        }
        $division_structure .= "[/td]";
        $division_structure .= "[/tr][/table]\r\n\r\n";


        /**
         * -----------LOAS------------
         */

        if (count((array)LeaveOfAbsence::find_all($this->game_id))) {
            $i = 1;

            // header
            $division_structure .= "\r\n[table='align:center,width: {$this->info_width}']";
            $division_structure .= "[tr][td]\r\n[center][size=3][color={$this->platoon_pos_color}][b]Leaves of Absence[/b][/color][/size][/center][/td][/tr]";
            $division_structure .= "[/table]\r\n\r\n";

            // players
            $division_structure .= "[table='align:center,width: {$this->info_width}']";
            $loas = LeaveOfAbsence::find_all($this->game_id);

            foreach ($loas as $player) {
                $date_end = (strtotime($player->date_end) < strtotime('now')) ? "[COLOR='#FF0000']Expired " . formatTime(strtotime($player->date_end)) . "[/COLOR]" : date("M d, Y", strtotime($player->date_end));
                $profile = Member::findByMemberId($player->member_id);
                $aod_url = Member::createAODlink(array('member_id'=>$player->member_id, 'forum_name'=>"AOD_".$profile->forum_name));

                $division_structure .= "[tr][td]{$aod_url}[/td][td]{$date_end}[/td][td]{$player->reason}[/td][/tr]";
                $i++;
            }

            $division_structure .= "[/table]";
        }

        $this->content = $division_structure;
    }
}
