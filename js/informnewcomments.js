function informNewComments(refreshInterval, discussionID, lastCommentID, informedCommentsCount){
    $.ajax({
        url: gdn.url('/discussion/informnewcomments/' + discussionID + '/' + lastCommentID + '/' + informedCommentsCount),
        dataType: 'json',
        cache: false,
        success: function(result){
            if (result) {
                gdn.informMessage(result.Message, {'CssClass': 'Dismissable'});
                informedCommentsCount = informedCommentsCount + result.NewCommentsCount;
            }
        }
    });
    setTimeout(
        function(){informNewComments(refreshInterval, discussionID, lastCommentID, informedCommentsCount)},
        refreshInterval
    ); 
}

$(document).ready(function(){
    var discussionID = definitions['DiscussionID'];
    var lastCommentID = definitions['LastCommentID'];
    var refreshInterval = gdn.definition('Plugins_InformNewComments_RefreshInterval', '60000');

    setTimeout(
        function(){informNewComments(refreshInterval, discussionID, lastCommentID, 0)}
        , refreshInterval
    );
});
