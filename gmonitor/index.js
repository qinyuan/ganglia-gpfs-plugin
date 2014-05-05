function openSubWin(href, title) {
    var frameIndex = $('#tt').tabs('tabs').length + 1;
    var iframeTpl = '<iframe src="' + href + '" id="iframe' + frameIndex
        + '" height="98%" width="99%"></iframe>';
    $('#tt').tabs('add', {
        'title': title,
        content: iframeTpl,
        closable: true
    });
}

function refreshTab() {
    var $iframe = $($('#tt').tabs('getSelected')).find('iframe');
    var id = $iframe.attr('id');
    window.frames[id].location.reload();
}

function updateNewWinHref(tabId) {
    var href = $($('#tt').tabs('getTab', tabId)).find('iframe').attr('src');
    $('#newWin').attr('href', href);
}

function changePage() {
    var selectedTree = $mainTree.tree('getSelected');
    var treeText = selectedTree.text;
    var hrefId = selectedTree.id;
    var $tabs = $('#tt');
    if ($tabs.tabs('getTab', treeText) === null) {
        if (hrefId) {
            $.get('data.php', {
                'data': 'href',
                'hrefId': hrefId
            }, function(href) {
                openSubWin(href, treeText);
                recordHrefStatus(hrefId);
            });
        }
    } else {
        $tabs.tabs('select', treeText);
        recordHrefStatus(hrefId);
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

$('#refresh').click(refreshTab);
$('#tt').tabs({
    'fit': true,
    'border': false,
    'plain': true,
    onSelect: function(title, index) {
        updateNewWinHref(index);
    }
});

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
