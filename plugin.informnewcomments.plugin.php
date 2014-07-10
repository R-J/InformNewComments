<?php if (!defined('APPLICATION')) exit();

$PluginInfo['InformNewComments'] = array(
    'Name' => 'Inform New Comments',
    'Description' => 'Shows inform message for new comments that has been written to the current discussion.',
    'Version' => '0.1',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'SettingsPermission' => 'Garden.Moderation.Manage',
    'SettingsUrl' => '/settings/informnewcoments',
    'MobileFriendly' => true,
    'Author' => 'Robin Jurinka',
    'License' => 'MIT'
);

/**
 * Shows InformMessage when there are new comments in current discussion.
 *
 * Periodically polls for new comments on the current discussion and gives feedback to the user
 *
 * @package InformNewComments
 * @author Robin Jurinka
 * @license MIT
 */
class InformNewCommentsPlugin extends Gdn_Plugin {
    /**
     * Appends JavaScript and config setting to a discussion.
     *
     * @param object $Sender DiscussionController.
     * @package InformNewComments
     * @since 0.1
     */
    public function discussionController_render_before($Sender) {
        $Sender->AddJsFile('informnewcomments.js', 'plugins/InformNewComments');
        $Sender->AddDefinition(
            'Plugins_InformNewComments_RefreshInterval',
            Gdn::Config('Plugins.InformNewComments.RefreshInterval', 60000)
        );
    }

    /**
     * Show info message if current discussion has unread comments.
     *
     * Is called by inserted javascript and displays a translatable message
     * if there are unread comments in current discussion.
     *
     * @param object $Sender DiscussionController.
     * @package InformNewComments
     * @since 0.1
     */
    public function discussionController_informNewComments_create($Sender) {
        $UserID = Gdn::Session()->UserID;
        // sanitize input to be a number
        $DiscussionID = $Sender->RequestArgs[0] + 0;
        $LastCommentID = $Sender->RequestArgs[1] + 0;
        $InformedCommentsCount = $Sender->RequestArgs[2] + 0;

        // get unread comment count for discussion
        $Sql = Gdn::SQL();
        $Sql->Select('c.CommentID', 'count', 'NewCommentsCount')
            ->From('Comment c')
            ->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
            ->Where('c.DiscussionID', $DiscussionID)
            ->Where('c.CommentID >', $LastCommentID)
            ->Where('c.InsertUserID <>', $UserID);

        // check permissions
        $Perms = DiscussionModel::CategoryPermissions();
        if ($Perms !== true) {
            $Sql->WhereIn('d.CategoryID', $Perms);
        }

        $NewCommentsCount = $Sql->Get()->FirstRow()->NewCommentsCount - $InformedCommentsCount;
        if ($NewCommentsCount > 0) {
            $CurrentTime = Gdn_Format::Date(time(), T('Date.DefaultTimeFormat', '%l:%M%p'));
            $Result = array(
                'NewCommentsCount' => $NewCommentsCount,
                'Message' => sprintf(
                    PluralTranslate(
                        $NewCommentsCount,
                        'This discussion has one new comment (%2$s)',
                        'This discussion has %1$s new comments (%2$s)'
                    ),
                    $NewCommentsCount,
                    $CurrentTime
                )
            );
            echo json_encode($Result);
        }
    }

    /**
     * Create minimal settings screen for the refresh interval.
     *
     * @param object $Sender SettingsController.
     * @package InformNewComments
     * @since 0.1
     */
    public function settingsController_informNewComents_create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');
        $Sender->SetData('Title', T('Inform New Comments Settings'));
        $Sender->AddSideMenu('dashboard/settings/plugins');

        $Conf = new ConfigurationModule($Sender);
        $Conf->Initialize(array(
            'Plugins.InformNewComments.RefreshInterval' => array(
                'Control' => 'textbox',
                'LabelCode' => T(
                    'InformNewCommentsSettings',
                    'Enter the refresh interval in milliseconds<br />(The more users you have, the higher that number should be!)'
                ),
                'Default' => '60000',
                'Options' => array('type' => 'number')
            )
        ));
        $Conf->RenderAll();
    }
}
