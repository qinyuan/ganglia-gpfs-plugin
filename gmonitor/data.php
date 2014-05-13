<?php
function get_modules() {
    $modules = array();
    $links_dir = dirname(__FILE__) . '/conf/links/';
    $files = scandir($links_dir);
    foreach ($files as $file) {
        if (preg_match("/^.*\.json$/", $file)) {
            $link = json_decode(file_get_contents($links_dir . $file));
            add_link($modules, $link);
        }
    }
    return $modules;
}

function add_link(&$modules, $link) {
    for ($i=0, $len = count($modules); $i < $len; $i++) {
        if ($modules[$i]['name'] === $link -> group) {
            $modules[$i]['links'][] = array(
                'href' => $link -> href,
                'text' => $link -> title,
            );
            return;
        }
    }

    $modules[] = array(
        'name' => $link -> group,
        'links' => array(
            array(
                'href' => $link -> href,
                'text' => $link -> title,
            ),
        ),
    );
}

function get_navi_tree_json() {
    $modules = get_modules();
    $menu = array();
    $id = 1;
    foreach ($modules as $module) {
        $children = array();
        foreach ($module['links'] as $link) {
            $children[] = array(
                'id' => $id++,
                'text' => $link['text'],
            );
        }
        $menu[] = array(
            "text" => $module['name'],
            'iconCls' => 'icon-module',
            "state" => "open",
            "children" => $children
        );
    }
    return $menu;
}

function get_href_by_id($id) {
    $modules = get_modules();
    $i=1;
    foreach ($modules as $module) {
        $links = $module['links'];
        foreach ($links as $link) {
            if ($id === $i) {
                return $link['href'];
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
