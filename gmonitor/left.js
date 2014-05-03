function changePage(e) {
    var parent = window.parent;
    if (parent !== window && parent.changePage) {
        var hrefId = $mainTree.tree('getSelected').id;
        if (hrefId) {
            $.get('data.php', {
                'data': 'href',
                'hrefId': hrefId
            }, function(href) {
                parent.changePage($.trim(href));
                recordHrefStatus(hrefId);
            });
        }
    }
}

function recordHrefStatus(hrefId) {
    $.cookie('latestHrefId', hrefId);
}

function loadLatestHrefId() {
    var latestHrefId = $.cookie('latestHrefId');
    if (!(latestHrefId && latestHrefId.match(/^\d+$/g))) {
        latestHrefId = 1;
    }
    var firstNode = $mainTree.tree('find', latestHrefId);
    if (firstNode) {
        $mainTree.tree('select', firstNode.target);
        changePage(latestHrefId);
    }
}

var $mainTree = $('#mainTree');
$.get('data.php', {
    "data": "treeJson"
}, function(data) {
    var treeJson;
    eval('treeJson=' + data + ";");
    $mainTree.tree({
        data: treeJson,
        onClick: changePage,
        onLoadSuccess: loadLatestHrefId
    });
});
