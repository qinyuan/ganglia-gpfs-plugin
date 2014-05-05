function openSubWin(href, title) {
    var iframeTpl = '<iframe src="' + href + 
        '" height="98%" width="99%"></iframe>';
    $('#tt').tabs('add', {
        'title': title,
        content: iframeTpl,
        closable: true
    });
}

function refreshTab() {
    var $iframe = $($('#tt').tabs('getSelected')).find('iframe');
    $iframe.attr('src', $iframe.attr('src'));
}

function updateNewWinHref(tabId) {
    var href = $($('#tt').tabs('getTab', tabId)).find('iframe').attr('src');
    $('#newWin').attr('href', href);
}

function changePage() {
    var selectedTree = $mainTree.tree('getSelected');
    var hrefId = selectedTree.id;
    if (hrefId) {
        $.get('data.php', {
            'data': 'href',
            'hrefId': hrefId
        }, function(href) {
            openSubWin(href, selectedTree.text);
            recordHrefStatus(hrefId);
        });
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
