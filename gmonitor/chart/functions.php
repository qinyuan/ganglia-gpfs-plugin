<?php
class PgConn {
    private $cnn;
    private $result;

    function __construct() {
        try {
        $this -> cnn = new PDO("pgsql:dbname=gpfsmonitor;host=localhost", "gpfsmonitor") 
            or die("PostgreSQL数据库连接失败！");
        } catch (Exception $e) {
            die("PostgreSQL数据库连接失败！");
        }
    }

    function query($query) {
        $this -> result = $this -> cnn -> query($query);
        $this -> result -> setFetchMode(PDO::FETCH_ASSOC);
    }

    function fetch() {
        return $this -> result -> fetch();
    }
}

/**
 * NodeStateInformer can tell you that given host is healthy or error
 */
class HostStateInformer {

    private $maxInterval = 120;
    private $hostStates = array();

    function __construct() {
        $cnn = new PgConn();
        $subQuery = "(select max(ts) as ts,host_id from entry group by host_id)";
        $query = "select now()-e1.ts as to_now,e1.host_id,state "
            ."from entry as e1 inner join $subQuery as e2 "
            ."on (e1.ts=e2.ts and e1.host_id=e2.host_id)";
        $cnn -> query($query);

        while ($row = $cnn -> fetch()) {
            $host_id = $row['host_id'];
            $this -> hostStates[$host_id] = array(
                'to_now' => trim($row['to_now']),
                'state' => $row['state']
            );
        }
    }

    function getHostState($host_id) {
        $to_now = $this -> hostStates[$host_id]['to_now'];
        $secs = get_secs_by_time_str($to_now);
        if ($secs === null || $secs > $this -> maxInterval) {
            return "unknown";
        }
        return $this -> hostStates[$host_id]['state'];
    }
}

function get_secs_by_time_str($to_now) {
    if (!preg_match("/^\d\d:\d\d:\d\d.*(\.\d+)?$/", $to_now)) {
        return null;
    }
    $str_arr = preg_split('/:/', $to_now);
    $secs = $str_arr[0] * 3600 + $str_arr[1] * 60 + $str_arr[2];
    return $secs;
}

/**
 * select data from PostgreSQL then convert the data to array
 */
function fetch_table($table_name, $fields = null, $where_clause = "") {
    $cnn = new PgConn();
    $query = "select";
    if ($fields === null) {
        $query .= ' * ';
    } else {
        $first = true;
        foreach ($fields as $field) {
            if ($first) {
                $first = false;
            } else {
                $query .= ',';
            }
            $query .= " $field";
        }
    }
    $query .= " from $table_name $where_clause";
    
    $cnn -> query($query);
    $rows = array();
    while (($row = $cnn -> fetch())) {
        if (isset($row['id'])) {
            $id = $row['id'];
            $rows[$id] = array();
            foreach ($row as $key => $value) {
                if ($key !== 'id' ) {
                    $rows[$id][$key] = $value;
                }
            }
        } else {
            $rows[] = $row;
        }
    }
    return $rows;
}

function get_clusters() {
    return fetch_table("clusters");
}

function get_hosts() {
    $fields = array('id', 'name', 'cluster_id');
    return fetch_table("hosts", $fields, "where cluster_id>0");
}

function get_state_colors() {
    return array(
        "active" => array("code" => "#009900", "desc" => "active"),
        "arbitrating" => array("code" => "#008080", "desc" => "arbitrating"),
        "error" => array("code" => "#8B0000", "desc" => "error"),
        "unknown" => array("code" => "#FF1493", "desc" => "unknown"),
        "down" => array("code" => "#2F4F4F", "desc" => "down"),
    );
}

function get_mount_colors() {
    return array(
        "ok" => array("code" => "#009900", "desc" => "ok"),
        "no" => array("code" => "#2F4F4F", "desc" => "no"),
        "stale" => array("code" => "#008080", "desc" => "stale"),
        "error" => array("code" => "#8B0000", "desc" => "error"),
        "unknown" => array("code" => "#FF1493", "desc" => "unknown"),
    );
}

/**
 * Receive string likes {"(3,t,ok,,,f)","(4,t,no,,,f)"}, 
 * then return array likes:
 * array (
 *   array (
 *     'fs_id' => 3,
 *     'mounted_state' => 'ok',
 *   ),
 *   array (
 *     'fs_id' => 4,
 *     'mounted_state' => 'no',
 *   )
 * )
 */
function parse_fs_mounted_states($str) {
    $str = str_replace('{"(', '', $str);
    $str = str_replace(')"}', '', $str);
    $str_arr = preg_split('/\)","\(/', $str);
    $fs_mounted_states = array();
    foreach ($str_arr as $s) {
        $s_arr = preg_split('/,/', $s);
        $fs_mounted_states[] = array(
            'fs_id' => $s_arr[0],
            'mounted_state' => $s_arr[2],
        );
    }
    return $fs_mounted_states;
}

/**
 * return array such as:
 * array(
 *   [1] => array(
 *     [4] = array(
 *       'fs_name' => 'gpfs1',
 *       'hosts' => array(
 *         array( 'host_id' => '3', 'mounted_state' => 'ok', 
 *           'host_name' => 'host3', 'color' => '#00ff00'),
 *         array( 'host_id' => '4', 'mounted_state' => 'no', 
 *           'host_name' => 'host4', 'color' => '#ff0000'),
 *         ...
 *       )
 *     ),
 *     [5] = array(
 *       ...
 *     ),
 *     ...
 *   ),
 *   [2] => array(
 *     ...
 *   )
 * )
 * the [1] and [2] is cluster id and [4] and [5] is filesystem id
 */
function get_filesystems() {
    define('MAX_INTERVAL', 120);
    $cnn = new PgConn();
    $subQuery = "(select max(ts) as ts,host_id from entry group by host_id)";
    $query = "select now()-e1.ts as to_now,e1.host_id,file_systems,"
            . "h.name as host_name,h.cluster_id "
            . "from entry as e1 inner join $subQuery as e2 "
            . "on (e1.ts=e2.ts and e1.host_id=e2.host_id) "
            . "inner join hosts as h on h.id=e1.host_id";
    $cnn -> query($query);
    $filesystems = array();
    $mount_colors = get_mount_colors();
    $filesystem_names = fetch_table("file_systems");
    while($row = $cnn -> fetch()) {
        $cluster_id = $row['cluster_id'];
        if (!isset($filesystems[$cluster_id])) {
            $filesystems[$cluster_id] = array();
        }
        $fs_mounted_states = parse_fs_mounted_states($row['file_systems']);
        $secs_to_now = get_secs_by_time_str($row['to_now']);
        foreach ($fs_mounted_states as $fs_mounted_state) {
            $fs_id = $fs_mounted_state['fs_id'];
            $mounted_state = ($secs_to_now === null || $secs_to_now > MAX_INTERVAL) ? 
                  'unknown' : $fs_mounted_state['mounted_state'];
            if (!isset($filesystems[$cluster_id][$fs_id])) {
                $filesystems[$cluster_id][$fs_id] = array(
                    'fs_name' => $filesystem_names[$fs_id]['gpfs_dev_name'],
                    'hosts' => array(),
                );
            }
            $filesystems[$cluster_id][$fs_id]['hosts'][] = array(
                'host_id' => $row['host_id'],
                'mounted_state' => $mounted_state,
                'host_name' => $row['host_name'],
                'color' => $mount_colors[$mounted_state]['code'],
            );
        }
    }
    return $filesystems;
}
?>
