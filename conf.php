<?php
function get_rrd_dir() {
    global $conf;
    $rrd_dir = $conf['rrds'];
    if (!preg_match("/^.*\/$/", $rrd_dir)) {
        $rrd_dir .= '/';
    }
    return $rrd_dir;
}

function get_gpfs_filesystems($cluster=null) {
    if ($cluster) {
        $sum_dir = get_rrd_dir() . $cluster . '/__SummaryInfo__/';
    } else {
        $sum_dir = get_rrd_dir() . '__SummaryInfo__/';
    }
    $files = scandir($sum_dir);
    $filesystems = array();
    foreach ($files as $file) {
        if (preg_match("/^gm_.*_mounted_accessible.rrd$/", $file)) { 
            $filesystem = preg_replace("/^gm_/", "", $file);
            $filesystem = preg_replace("/_mounted_accessible.rrd$/", "", $filesystem);
            $filesystems[] = $filesystem;
        }
    }
    return $filesystems;
}

function get_normal_rrd_sub_dir($dir){
    $dirs = scandir($dir);
    $result = array();
    foreach ($dirs as $d) {
        if ($d !== '.' && $d !== '..' && $d !== '__SummaryInfo__' 
            && is_dir($dir . $d)) {
            $result[] = $d;
        }
    }
    return $result;
}

function get_clusters() {
    $rrd_dir = get_rrd_dir();
    $subdirs = get_normal_rrd_sub_dir(get_rrd_dir());
    $clusters = array();
    foreach ($subdirs as $subdir) {
        if (contains_gpfs_io_rrd($rrd_dir . $subdir . '/__SummaryInfo__/')) {
            $clusters[] = $subdir;
        }
    }
    return $clusters;
}

function contains_gpfs_io_rrd($full_dir_path) {
    $rrd_files = scandir($full_dir_path);
    foreach ($rrd_files as $rrd_file) {
        if (preg_match("/^gpfs_io_[r|w]ps_.*\.rrd$/",$rrd_file)) {
            return true;
        }
    }
    return false;
}

function get_hosts_by_cluster($cluster) {
    $dir = get_rrd_dir() . $cluster . '/';
    $hosts =  get_normal_rrd_sub_dir($dir);
    $gpfs_hosts = array();
    foreach ($hosts as $host) {
        $host_dir = $dir . $host . '/';
        if (contains_gpfs_io_rrd($host_dir)) {
            $gpfs_hosts[] = $host;
        }
    }
    return $gpfs_hosts;
}

function get_gpfs_io_item_json($cluster, $hostname = null) {
    $json = array(
        'graph' => 'gpfs_io_report',
        'cluster' => $cluster,
        'size' => 'medium'
    );
    if ($hostname !== null) {
        $json['hostname'] = $hostname;
    }
    return $json;
}

function json_to_str($json, $space = ""){
    $str = "$space{";
    $first = true;
    foreach ($json as $key => $value) {
        $type = gettype($value);
        if ($first) {
            $first = false;
        } else {
            $str .= ',';
        }
        if ($type === 'string') {
            $str .= "\n$space\t\"$key\": \"$value\"";
        } else if ($type === 'array') {
            $str .= "\n$space\t\"$key\": [";
            $inner_first = true;
            foreach ($value as $v) {
                if ($inner_first) {
                    $inner_first = false;
                } else {
                    $str .= ',';
                }
                $str .= "\n" . json_to_str($v, "$space\t\t");
            }
            $str .= "\n$space\t]";
        }
    }
    $str .= "\n$space}";
    return $str;
}

function get_gweb_config_dir() {
    return '/var/lib/ganglia-web/conf/';
}

function config_gpfs_io_view(){
    $gpfs_io_conf_json = array(
        'view_name' => 'gpfs_io',
        'view_type' => 'standard'
    );
    $items = array();
    $clusters = get_clusters();
    foreach ($clusters as $cluster) {
        $items[] = get_gpfs_io_item_json($cluster);
        $hosts = get_hosts_by_cluster($cluster);
        foreach ($hosts as $host) {
            $items[] = get_gpfs_io_item_json($cluster, $host);
        }
    }
    $gpfs_io_conf_json['items'] = $items;
    $json_str = json_to_str($gpfs_io_conf_json);
    $view_gpfs_io_conf_file = get_gweb_config_dir() . 'view_gpfs_io.json';
    file_put_contents($view_gpfs_io_conf_file, $json_str);
}

function config_gpfs_state_view() {
    $gpfs_state_json = array(
        'view_name' => 'gpfs_state',
        'view_type' => 'standard'
    );
    $clusters = get_clusters();
    $items = array();
    foreach ($clusters as $cluster) {
    	$filesystems = get_gpfs_filesystems($cluster);
        foreach ($filesystems as $fs) {
            $items[] = array(
                "graph" => "gm_{$fs}_report",
                "cluster" => $cluster,
                "size" => "medium"
            );
        }
        $items[] = array(
            "graph" => "gm_nodes_report",
            "cluster" => $cluster,
            "size" => "medium"
        );
        $items[] = array(
            "graph" => "gm_state_report",
            "cluster" => $cluster,
            "size" => "medium"
        );
        $items[] = array(
            "graph" => "gm_waiter_count_report",
            "cluster" => $cluster,
            "size" => "medium"
        );
        $items[] = array(
            "graph" => "gm_waiter_time_report",
            "cluster" => $cluster,
            "size" => "medium"
        );
    }
    $gpfs_state_json['items'] = $items;
    $json_str = json_to_str($gpfs_state_json);
    $gpfs_state_view_conf_file = get_gweb_config_dir() . 'view_gpfs_state.json';
    file_put_contents($gpfs_state_view_conf_file, $json_str);
}

function config_gpfs_fs_report() {
    $graph_path = getcwd() . "/graph.d/";
    $tpl_file = $graph_path . "gm_gpfs_report.php.tpl";
    if ( !(is_dir($graph_path) && is_file($tpl_file)) ) {
        return;
    }

    $tpl_file_content = file_get_contents($tpl_file);
    $filesystems = get_gpfs_filesystems();
    foreach ($filesystems as $fs) {
        $conf_file_name = $graph_path . "gm_{$fs}_report.php";
        $conf_file_content = str_replace('{$gpfs_fs_name}', $fs, $tpl_file_content);
        file_put_contents($conf_file_name, $conf_file_content);
    }
}

config_gpfs_io_view();
config_gpfs_state_view();
config_gpfs_fs_report();
?>
