<?php
$conf_dir = '/var/www/html/gmonitor/conf/';

function get_modules_json() {
    global $conf_dir;
    $modules_conf_file = $conf_dir . 'modules.json';
    $modules_json = json_decode(file_get_contents($modules_conf_file));
    return $modules_json;
}

function get_navi_tree_json() {
    $modules_json = get_modules_json();
    $menu = array(
        'text' => '监控模块',
        "children" => array()
    );
    $id = 1;
    foreach ($modules_json as $module_json) {
        $module = array(
            "text" => $module_json -> name,
            "state" => "open",
            "children" => array()
        );
        $links = $module_json -> links;
        foreach ($links as $link) {
            $module['children'][] = array(
                'id' => $id++,
                'text' => $link -> text,
            );
        }
        $menu["children"][] = $module;
    }
    return array($menu);
}

function get_href_by_id($id) {
    $modules_json = get_modules_json();
    $i=1;
    foreach ($modules_json as $module_json) {
        $links = $module_json -> links;
        foreach ($links as $link) {
            if ($id === $i) {
                return $link -> href;
            }
            $i++;
        }
    }
    return null;
}

if (isset($_GET['data'])) {
    switch ($_GET['data']) {
        case 'treeJson':
            echo json_encode(get_navi_tree_json());
            break;
        case 'href':
            if (isset($_GET['hrefId']) && $_GET['hrefId']) {
                $hrefId = intval($_GET['hrefId']);
                echo get_href_by_id($hrefId);
            }
            break;
        default:
            echo "this should not heppen";
            break;
    }
}
?>
