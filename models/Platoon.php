<?php

use Carbon\Carbon;

class Platoon extends Application
{
    public static $id_field = "id";
    public static $table = "platoon";
    public $id;
    public $number;
    public $name;
    public $game_id;
    public $leader_id;

    public static function find_all($game_id)
    {
        $conditions = array('game_ID' => $game_id);
        $params = Flight::aod()->from(self::$table)->sortAsc('number')->where($conditions)->select()->many();
        return arrayToObject($params);
    }

    public static function countPlatoons()
    {
        return self::count_all();
    }

    public static function findById($platoon_id)
    {
        $sql = "SELECT p.id, p.number, p.name, p.leader_id, m.forum_name, r.abbr FROM " . Platoon::$table . " p LEFT JOIN " . Member::$table . " m on p.leader_id = m.member_id LEFT JOIN " . Rank::$table . " r on m.rank_id = r.id WHERE p.id = {$platoon_id}";
        $params = Flight::aod()->sql($sql)->one();
        return arrayToObject($params);
    }

    public static function findByName($name)
    {
        $conditions = array('name %' => "%{$name}%");
        $params = Flight::aod()
            ->from(Platoon::$table)
            ->where($conditions)
            ->select()
            ->one();
        return arrayToObject($params);
    }

    public static function Leader($leader_id)
    {
        $params = Member::findById($leader_id);
        return arrayToObject($params);
    }

    public static function SquadLeaders($game_id, $platoon_id = false, $order_by_rank = false)
    {
        $m = Member::$table;
        $p = Platoon::$table;
        $s = Squad::$table;

        $sql = "SELECT m.id, member_id, rank_id, forum_name, p.name as platoon_name FROM {$m} m LEFT JOIN {$p} p ON p.id = m.platoon_id WHERE position_id = 5 AND m.game_id = {$game_id} AND m.id NOT IN (SELECT leader_id FROM {$s})";

        if ($platoon_id) {
            $sql .= " AND platoon_id = {$platoon_id} ";
        }

        if ($order_by_rank) {
            $sql .= " ORDER BY m.rank_id DESC, m.forum_name ASC ";
        } else {
            $sql .= " ORDER BY p.id, forum_name";
        }

        $params = Flight::aod()->sql($sql)->many();
        return arrayToObject($params);
    }

    public static function gameStats($platoon_id, $bdate, $edate)
    {
        $members = self::memberIdsList($platoon_id);
        $total = BF_BfActivity::findTotalGamesByArray($members, $bdate, $edate);
        $AOD = BF_BfActivity::findTotalAODGamesByArray($members, $bdate, $edate);
        return array(
            'pct' => round(array_sum($AOD) / array_sum($total) * 100),
            'total' => array_sum($total),
            'AOD' => array_sum($AOD)
        );
    }

    public static function memberIdsList($platoon_id)
    {
        $sql = "SELECT member_id FROM " . Member::$table . " WHERE platoon_id = {$platoon_id} AND status_id IN (1, 999)";
        $params = Flight::aod()->sql($sql)->many();
        if (count($params)) {
            foreach ($params as $member) {
                $memberIds[] = intval($member['member_id']);
            }
            return $memberIds;
        } else {
            return false;
        }
    }

    public static function forumActivity($platoon_id)
    {
        $twoWeeksAgo = Carbon::now()->subDays(14);
        $oneMonthAgo = Carbon::now()->subDays(30);

        $conditions = "status_id = 1 AND platoon_id = {$platoon_id}";

        $underTwoWeeks = Flight::aod()->sql('SELECT count(*) as count FROM ' . Member::$table . ' WHERE ' . $conditions . " AND last_activity >= '{$twoWeeksAgo}'")->one();

        $twoWeeksMonth = Flight::aod()->sql('SELECT count(*) as count FROM ' . Member::$table . ' WHERE ' . $conditions . " AND last_activity <= '{$twoWeeksAgo}' AND last_activity >= '{$oneMonthAgo}'")->one();

        $oneMonth = Flight::aod()->sql('SELECT count(*) as count FROM ' . Member::$table . ' WHERE ' . $conditions . " AND last_activity <= '{$oneMonthAgo}'")->one();

        // generate json for graph
        $data = array();
        $data[] = array(
            'label' => '< 2 weeks ago',
            'color' => '#28b62c',
            'highlight' => '#5bc75e',
            'value' => $underTwoWeeks['count']
        );
        $data[] = array(
            'label' => '14 - 30 days',
            'color' => '#ff851b',
            'highlight' => '#ffa14f',
            'value' => $twoWeeksMonth['count']
        );
        $data[] = array(
            'label' => '> 30 days',
            'color' => '#ff4136',
            'highlight' => '#ff6c64',
            'value' => $oneMonth['count']
        );

        return json_encode($data);
    }

    public static function unassignedMembers($platoon_id)
    {
        $conditions = array(
            'platoon_id' => $platoon_id,
            'status_id @' => array(1, 3, 999),
            'squad_id' => 0,
            'position_id @' => array(6, 7, 0)
        );
        return arrayToObject(Flight::aod()->from(Member::$table)->where($conditions)->SortDesc('rank_id')->many());
    }

    public static function countSquadLeaders($platoon_id)
    {
        $sql = "SELECT count(*) as count FROM " . Member::$table . " WHERE position_id = 5 AND platoon_id = {$platoon_id}";
        $params = Flight::aod()->sql($sql)->one();
        return $params['count'];
    }

    public static function countSquadMembers($platoon_id)
    {
        $sql = "SELECT count(*) as count FROM " . Member::$table . " WHERE position_id = 6 AND platoon_id = {$platoon_id}";
        $params = Flight::aod()->sql($sql)->one();
        return $params['count'];
    }

    public static function countGeneralPop($platoon_id)
    {
        $sql = "SELECT count(*) as count FROM " . Member::$table . " WHERE member.position_id = 7 AND (status_id = 1 OR status_id = 999) AND platoon_id = {$platoon_id}";
        $params = Flight::aod()->sql($sql)->one();
        return $params['count'];
    }

    public static function countPlatoon($platoon_id)
    {
        return count(self::members($platoon_id));
    }

    public static function members($platoon_id)
    {
        $conditions = array('platoon_id' => $platoon_id, 'status_id @' => array(1, 999));
        $select = array(
            'member.id as memberid',
            'forum_name',
            'member_id',
            'rank_id',
            'position_id',
            'join_date',
            'last_forum_login',
            'last_activity',
            'position.desc',
            'game_id'
        );
        $params = Flight::aod()->from(Member::$table)
            ->join('position', array('position.id' => 'member.position_id'))
            ->sortAsc('position.sort_order')
            ->where($conditions)
            ->select($select)->many();
        return $params;
    }

    public static function getIdFromNumber($platoon_number, $division)
    {
        $sql = "SELECT id FROM " . Platoon::$table . " WHERE number = {$platoon_number} AND game_id = {$division}";
        $params = Flight::aod()->sql($sql)->one();
        return $params['id'];
    }

    public static function get_number_from_id($platoon_id)
    {
        $sql = "SELECT number FROM " . Platoon::$table . " WHERE id = {$platoon_id}";
        $params = Flight::aod()->sql($sql)->one();
        return $params['number'];
    }

    public static function modify($params)
    {
        $platoon = new self();
        foreach ($params as $key => $value) {
            $platoon->$key = $value;
        }
        $platoon->update($params);
    }
}
