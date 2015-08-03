<?php defined('APPLICATION') or die;

$PluginInfo['InformNewComments'] = array(
    'Name' => 'Inform New Comments',
    'Description' => 'Shows inform message for new comments that has been written to the current discussion.',
    'Version' => '0.4',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'SettingsPermission' => 'Garden.Moderation.Manage',
    'SettingsUrl' => '/settings/informnewcoments',
    'MobileFriendly' => true,
    'Author' => 'Robin Jurinka',
    'AuthorUrl' => 'http://vanillaforums.org/profile/44046/R_J',
    'License' => 'MIT'
);

/**
 * Shows InformMessage when there are new comments in current discussion.
 *
 * Periodically polls for new comments on the current discussion and gives
 * feedback to the user.
 *
 * @package InformNewComments
 * @author Robin Jurinka
 * @license MIT
 */
class InformNewCommentsPlugin extends Gdn_Plugin {
    /**
     * Appends JavaScript and config setting to a discussion.
     *
     * @param object $sender DiscussionController.
     * @package InformNewComments
     * @since 0.1
     */
    public function discussionController_render_before($sender) {
        $sender->addJsFile('informnewcomments.js', 'plugins/InformNewComments');
        $sender->addDefinition(
            'InformNewComments_RefreshInterval',
            Gdn::config('InformNewComments.RefreshInterval', 60000)
        );
    }

    /**
     * Show info message if current discussion has unread comments.
     *
     * Is called by inserted javascript and displays a translatable message
     * if there are unread comments in current discussion.
     *
     * @param object $sender DiscussionController.
     * @package InformNewComments
     * @since 0.1
     */
    public function discussionController_informNewComments_create($sender) {
        $userID = Gdn::session()->UserID;
        // Sanitize input to be an integer.
        $discussionID = (int)$sender->RequestArgs[0];
        $lastCommentID = (int)$sender->RequestArgs[1];
        $informedCommentsCount = (int)$sender->RequestArgs[2];

        // Get unread comment count for discussion.
        $sql = Gdn::sql();
        $sql->select('1', 'count', 'NewCommentsCount')
            ->from('Comment c')
            ->join('Discussion d', 'c.DiscussionID = d.DiscussionID')
            ->where('c.DiscussionID', $discussionID)
            ->where('c.CommentID >', $lastCommentID)
            ->where('c.InsertUserID <>', $userID);

        // Do category permission check.
        $permissions = DiscussionModel::categoryPermissions();
        if ($permissions !== true) {
            $sql->whereIn('d.CategoryID', $permissions);
        }

        $newCommentsCount = $sql->get()->firstRow()->NewCommentsCount - $informedCommentsCount;
        if ($newCommentsCount) {
            $currentTime = Gdn_Format::date(time(), t('Date.DefaultTimeFormat', '%l:%M%p'));
            $result = array(
                'NewCommentsCount' => $newCommentsCount,
                'Message' => sprintf(
                    pluralTranslate(
                        $newCommentsCount,
                        'This discussion has one new comment (%2$s)',
                        'This discussion has %1$s new comments (%2$s)'
                    ),
                    $newCommentsCount,
                    $currentTime
                )
            );
            echo json_encode($result);
        }
    }

    /**
     * Create minimal settings screen for the refresh interval.
     *
     * @param object $sender SettingsController.
     * @package InformNewComments
     * @since 0.1
     */
    public function settingsController_informNewComents_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', t('Inform New Comments Settings'));
        $sender->addSideMenu('dashboard/settings/plugins');

        $configurationModule = new ConfigurationModule($sender);
        $configurationModule->initialize(array(
            'InformNewComments.RefreshInterval' => array(
                'Control' => 'TextBox',
                'LabelCode' => 'Enter the refresh interval in milliseconds',
                'Description' => 'The more users you have, the higher that number should be!',
                'Default' => '60000',
                'Options' => array('type' => 'number')
            )
        ));
        $configurationModule->renderAll();
    }
}
