<?php
require_once 'Smarty/Smarty.class.php';
require_once 'functions.php';
$clusters = get_clusters();
$hosts = get_hosts();
$host_state_informer = new HostStateInformer();
$state_colors = get_state_colors();
foreach ($hosts as $host_id => $host) {
    $host['state'] = $host_state_informer -> getHostState($host_id);
    if (isset($state_colors[$host['state']])) {
        $host['state_color'] = $state_colors[$host['state']]['code'];
    } else {
        $host['state_color'] = $state_colors['error']['code'];
    }
    $cluster_id = $host['cluster_id'];
    if (!isset($clusters[$cluster_id]['hosts'])) {
        $clusters[$cluster_id]['hosts'] = array();
    }
    $clusters[$cluster_id]['hosts'][] = $host;
}

$file_systems = get_filesystems();
$mount_colors = get_mount_colors();
foreach ($file_systems as $cluster_id => $cluster_fs) {
    $clusters[$cluster_id]['filesystems'] = $cluster_fs;
}

$smarty = new Smarty();
$time_str = date('Y-m-d H:i:s') . ' (' . date_default_timezone_get() . ')';
$smarty -> assign('current_time', $time_str);
$smarty -> assign('state_colors', $state_colors);
$smarty -> assign('mount_colors', get_mount_colors());
$smarty -> assign('clusters', $clusters);
$smarty -> display('index.tpl');
?>
