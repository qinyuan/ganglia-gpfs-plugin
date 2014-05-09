<?php
function get_rrd_dir() {
    global $conf;
    $rrd_dir = $conf['rrds'];
    if (!preg_match("/^.*\/$/", $rrd_dir)) {
        $rrd_dir .= '/';
    }
    return $rrd_dir;
}

function get_gpfs_filesystems($cluster = null, $host = null) {
    if ($cluster) {
        if ($host) {
            $dir = get_rrd_dir() . "$cluster/$host/";
        } else {
            $dir = get_rrd_dir() . $cluster . '/__SummaryInfo__/';
        }
    } else {
        $dir = get_rrd_dir() . '__SummaryInfo__/';
    }
    $files = scandir($dir);
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

$hosts_arr = null;
function get_hosts_by_ip($search_ip) {
    global $hosts_arr;
    if ($hosts_arr === null) {
        $hosts_arr = array();
        $host_conf_str = file_get_contents('/etc/hosts');

        $lines = preg_split("/\n/", $host_conf_str);
        foreach ($lines as $line) {
            if (($line = trim($line)) === '') {
                continue;
            }
            $line_arr = preg_split("/[\s|,]+/", $line);
            $ip = $line_arr[0];
            $hosts_arr[$ip] = array();
            for ($i=1, $len = count($line_arr); $i < $len; $i++) {
                $hosts_arr[$ip][] = $line_arr[$i];
            }
        }
    }
    return $hosts_arr[$search_ip];
}


/**
 * return array such as:
 * Array(
 *   [gpfs_cluster1] => Array(
 *     [ip] => '192.168.8.41',
 *     [host] => Array(
 *       [0] => 'node3',
 *       [1] => 'host3',
 *       [2] => 'gpfs_cluster1'
 *     ),
 *   ),
 *   [gpfs_cluster2] => Array(
 *     ...
 *   ),
 *   ...
 * )
 */
function get_gpfs_clusters() {
    $gpfsreporter_conf = file_get_contents('/etc/gpfsreporter.conf');
    $gpfsreporter_conf = preg_replace("/\#[^\n]*\n/", "", $gpfsreporter_conf);
    $start = strpos($gpfsreporter_conf, 'RegisterClusterPseudoIP');
    $end = strpos($gpfsreporter_conf, ');', $start);
    $gpfs_clusters_str = substr($gpfsreporter_conf, $start, $end - $start);
    $gpfs_clusters_str = str_replace('RegisterClusterPseudoIP(', '', $gpfs_clusters_str);

    $gpfs_clusters = array();
    $lines = preg_split("/,\s/", $gpfs_clusters_str);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $key_value_pairs = preg_split("/\s*\=\>\s*/", $line);
            $cluster_name = preg_replace("/\'|\"/", '', $key_value_pairs[0]);
            $ip = preg_replace("/\'|\"/", '', $key_value_pairs[1]);
            $hosts = get_hosts_by_ip($ip);
            $hosts[] = $cluster_name;
            $gpfs_clusters[$cluster_name] = array(
                'ip' => $ip,
                'hosts' => $hosts,
            );
        }
    }
    return $gpfs_clusters;
}

function contains_rrd_files($full_dir_path, $pattern) {
    $rrd_files = scandir($full_dir_path);
    foreach ($rrd_files as $rrd_file) {
        if (preg_match($pattern, $rrd_file)) {
            return true;
        }
    }
    return false;
}

function contains_gpfs_io_rrd($full_dir_path) {
    return contains_rrd_files($full_dir_path, "/^gpfs_io_[r|w]ps_.*\.rrd$/");
}

function contains_gpfs_state_rrd($full_dir_path) {
    return contains_rrd_files($full_dir_path, "/^gm_.*\.rrd$/");
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

function get_gpfs_cluster_host($cluster_name) {
    $g_clusters = get_gpfs_clusters();
    $g_cluster = $g_clusters[$cluster_name];
    $g_hosts = $g_cluster['hosts'];
    foreach ($g_hosts as $g_host) {
        $clusters = get_clusters();
        foreach ($clusters as $cluster) {
            $hosts = get_hosts_by_cluster($cluster);
            foreach ($hosts as $host) {
                if ($host === $g_host) {
                    $dir = get_rrd_dir() . "$cluster/$host/";
                    if (contains_gpfs_state_rrd($dir) ){
                        return array(
                            'cluster' => $cluster,
                            'host' => $host,
                        );
                    }
                }
            }
        }
    }
    return null;
}

function clear_gpfs_state_views() {
    $dir = get_gweb_config_dir();
    $files = scandir($dir);
    foreach ($files as $file) {
        if (preg_match("/^view_.*_state.json$/", $file)) {
            unlink($dir . $file);
        }
    }
}

function config_gpfs_state_view($gpfs_cluster_name = null) {
    if ($gpfs_cluster_name == null) {
        clear_gpfs_state_views();
        $gpfs_clusters = get_gpfs_clusters();
        foreach (array_keys($gpfs_clusters) as $key) {
            config_gpfs_state_view($key);
        }
        return;
    }
    
    $gpfs_cluster_host = get_gpfs_cluster_host($gpfs_cluster_name);
    if ($gpfs_cluster_host === null ) {
        return;
    }
    $cluster = $gpfs_cluster_host['cluster'];
    $host = $gpfs_cluster_host['host'];

    $gpfs_state_json = array(
        'view_name' => $gpfs_cluster_name . '_state',
        'view_type' => 'standard'
    );

    $items = array();
  	$filesystems = get_gpfs_filesystems($cluster, $host);
    foreach ($filesystems as $fs) {
        $items[] = array(
            "graph" => "gm_{$fs}_report",
            "cluster" => $cluster,
            'hostname' => $host,
            "size" => "medium"
        );
    }
    $items[] = array(
        "graph" => "gm_nodes_report",
        "cluster" => $cluster,
        "hostname" => $host,
        "size" => "medium"
    );
    $items[] = array(
        "graph" => "gm_state_report",
        "cluster" => $cluster,
        "hostname" => $host,
        "size" => "medium"
    );
    $items[] = array(
        "graph" => "gm_waiter_count_report",
        "cluster" => $cluster,
        "hostname" => $host,
        "size" => "medium"
    );
    $items[] = array(
        "graph" => "gm_waiter_time_report",
        "cluster" => $cluster,
        "hostname" => $host,
        "size" => "medium"
    );
    $gpfs_state_json['items'] = $items;
    $json_str = json_to_str($gpfs_state_json);
    $gpfs_state_view_conf_file = get_gweb_config_dir() . "view_${gpfs_cluster_name}_state.json";
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
