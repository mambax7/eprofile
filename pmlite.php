<?php
/**
 * Private message module
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code 
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         pm
 * @since           2.3.0
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id: pmlite.php 2 2012-08-16 08:20:47Z alfred $
 */

$xoopsOption['pagetype'] = 'user';
include 'header.php';
$xoopsOption['xoops_pagetitle'] = sprintf(_US_ALLABOUT, $xoopsUser->getVar('name'));
if (!is_object($xoopsUser)) {
  redirect_header(XOOPS_URL, 3, _NOPERM);
}
xoops_loadLanguage('pmsg');

$xoopsConfig['module_cache'] = 0; //disable caching since the URL will be the same, but content different from one user to another
$xoopsOption['template_main'] = "profile_pmwrite.html";
include $GLOBALS['xoops']->path( '/header.php' );     
$xoopsTpl->assign('section_name', _EPROFILE_MA_PMNAME);
include_once "include/themeheader.php";

$reply      = !empty($_GET['reply']) ? 1 : 0;
$send       = !empty($_GET['send']) ? 1 : 0;
$send2      = !empty($_GET['send2']) ? 1 : 0;
$sendmod    = !empty($_POST['sendmod']) ? 1 : 0; // send from other modules with post data
$to_userid  = isset($_GET['to_userid']) ? intval($_GET['to_userid']) : 0;
$msg_id     = isset($_GET['msg_id']) ? intval($_GET['msg_id']) : 0;

if ( empty($_GET['refresh']) && isset($_POST['op']) && $_POST['op'] != "submit" ) {
    $jump = "pmlite.php?refresh=" . time();
    if ( $send == 1 ) {
        $jump .= "&amp;send={$send}";
    } elseif ( $send2 == 1 ) {
        $jump .= "&amp;send2={$send2}&amp;to_userid={$to_userid}";
    } elseif ( $reply == 1 ) {
        $jump .= "&amp;reply={$reply}&amp;msg_id={$msg_id}";
    } else {
    }
    header('location: ' . $jump);
    exit();
}

//xoops_header();

$myts =& MyTextSanitizer::getInstance();
if (isset($_POST['op']) && $_POST['op'] == "submit") {
    $member_handler =& xoops_gethandler('member');
    $count = $member_handler->getUserCount(new Criteria('uid', intval($_POST['to_userid'])));
    $profileconfigs_handler = xoops_getmodulehandler('configs','eprofile');
    $criteria = new Criteria('config_uid',intval($_POST['to_userid']));
    $configs = $profileconfigs_handler->getObjects($criteria);
    $config = ($configs) ? $configs[0] : null;    
    echo '<div class="odd">';
    if ($count != 1) {
        echo "<br /><br /><div><h4>"._PM_USERNOEXIST."<br />";
        echo _PM_PLZTRYAGAIN."</h4><br />";
        echo "[ <a href='readpmsg.php'>"._PM_GOBACK."</a> ]</div>";
    } elseif ($GLOBALS['xoopsSecurity']->check()) {
        if (!empty($config) && $config->getVar('profile_messages') < 1) {
            echo "<br /><br /><div><h4>"._EPROFILE_MA_NOPERM."<br />";
            echo "[ <a href='readpmsg.php'>"._PM_GOBACK."</a> ]</div>";
            echo '</div>';
            xoops_footer();
            exit();
        }
        $pm_handler =& xoops_getModuleHandler('message', 'eprofile');
        //Read latest PM send
        if ( !$pm_handler->readLastPm(10, 5) ) {
            echo "<br /><br /><div><h4>"._EPROFILE_MA_NOPERM."<br />";
            echo "[ <a href='readpmsg.php'>"._PM_GOBACK."</a> ]</div>";
            echo '</div>';
            xoops_footer();
            exit();
        }
        $pm =& $pm_handler->create();
        $pm->setVar("msg_time", time());
        $pm->setVar("subject", $_POST['subject']);
        $pm->setVar("msg_text", $_POST['message']);
        $pm->setVar("to_userid", intval($_POST['to_userid']));
        $pm->setVar("from_userid", $xoopsUser->getVar("uid"));
        if (isset($_REQUEST['savecopy']) && $_REQUEST['savecopy'] == 1) {
            //PMs are by default not saved in outbox
            $pm->setVar('from_delete', 0);
        }
        if (!$pm_handler->insert($pm)) {
            echo $pm->getHtmlErrors();
            echo "<br />[ <a href='readpmsg.php'>"._PM_GOBACK."</a> ]</div>";
        } else {
            // Send a Private Message email notification
            $toUser =& $member_handler->getUser(intval($_POST['to_userid']));                     
            // Only send email notif if notification method is mail
            if ( $profileconfigs_handler->getperm('messages_notify', $toUser->uid(), $muid) ) {
                $xoopsMailer =& xoops_getMailer();
                $xoopsMailer->reset();
                $xoopsMailer->useMail();
                $xoopsMailer->setTemplate('pm_new.tpl'); 
                $xoopsMailer->setTemplateDir($xoopsModule->getVar('dirname', 'n'));    
                $xoopsMailer->setToUsers($toUser);  
                $xoopsMailer->assign('X_SITENAME', $GLOBALS['xoopsConfig']['sitename']);
                $xoopsMailer->assign('X_SITEURL', XOOPS_URL."/");
                $xoopsMailer->assign('X_ADMINMAIL', $GLOBALS['xoopsConfig']['adminmail']);
                $xoopsMailer->assign('X_UNAME', $toUser->uname());
                $xoopsMailer->assign('X_FROMUNAME', $xoopsUser->uname());
                $xoopsMailer->assign('X_SUBJECT', $myts->stripSlashesGPC($_POST['subject']));
                $xoopsMailer->assign('X_MESSAGE', $myts->stripSlashesGPC($_POST['message']));
                $xoopsMailer->assign('X_ITEM_URL', XOOPS_URL . "/modules/".$xoopsModule->dirname()."/viewpmsg.php");
                $xoopsMailer->setSubject(_EPROFILE_MA_PMMAILNOTIFYSUBJECT);
                $xoopsMailer->send(); 
            }            
            
            echo "<br /><br /><div style='text-align:center;'><h4>" . _PM_MESSAGEPOSTED . '</h4><br /><a href="'.XOOPS_URL.'/viewpmsg.php">'._PM_CLICKHERE.'</a></div>';
        }
    }
    else {
        echo implode('<br />', $GLOBALS['xoopsSecurity']->getErrors());
        include $GLOBALS['xoops']->path( '/footer.php' ); 
        exit();
    }
    echo '</div>';
        
} elseif ($reply == 1 || $send == 1 || $send2 == 1 || $sendmod =1) {
    if ($reply == 1) {
        $pm_handler =& xoops_getModuleHandler('message', 'eprofile');
        $pm =& $pm_handler->get($msg_id);
        if ($pm && $pm->getVar("to_userid") == $xoopsUser->getVar('uid')) {
          if ( $profileconfigs_handler->getperm('profile_messages', $pm->getVar("from_userid"), $muid) ) {
            $pm_uname = XoopsUser::getUnameFromId($pm->getVar("from_userid"));
            $message  = "[quote]\n";
            $message .= sprintf(_EPROFILE_MA_PMUSERWROTE , $pm_uname);
            $message .= "\n" . $pm->getVar("msg_text", "E") . "\n[/quote]";
          } else {
            redirect_header(XOOPS_URL . "/modules/eprofile/viewpmsg.php", 3, _MA_EPROFILE_PMISDISABLED);
          }
        } else {
            //unset($pm);
            //$reply = $send2 = 0;
            redirect_header(XOOPS_URL . "/modules/eprofile/viewpmsg.php", 3, _NOPERM);
        }
    }
    
    include_once XOOPS_ROOT_PATH . "/class/template.php";
    //$xoopsTpl = new XoopsTpl();
    include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
    $pmform = new XoopsForm('', 'pmform', 'pmlite.php', 'post', true);
    
    if ($reply == 1) {
        $subject = $pm->getVar('subject', 'E');
        //ToDo !
        if (!preg_match("/^(Re:|Aw:)/i", $subject)) {
            $subject = 'Aw: ' . $subject;
        }
        $xoopsTpl->assign('to_username', $pm_uname);
        $pmform->addElement(new XoopsFormHidden('to_userid', $pm->getVar("from_userid")));
        $pmform->addElement(new XoopsFormLabel(_EPROFILE_MA_PMTO, XoopsUser::getUnameFromId($pm->getVar("from_userid"))));
    } elseif ($sendmod == 1) {
        $xoopsTpl->assign('to_username', XoopsUser::getUnameFromId($_POST["to_userid"]));
        $pmform->addElement(new XoopsFormHidden('to_userid', $_POST["to_userid"]));
        $subject = $myts->htmlSpecialChars($myts->stripSlashesGPC($_POST['subject']));
        $message = $myts->htmlSpecialChars($myts->stripSlashesGPC($_POST['message']));
    } else {
        if ($send2 == 1) {
            $to_username = XoopsUser::getUnameFromId($to_userid);
            $pmform->addElement(new XoopsFormLabel(_EPROFILE_MA_PMTO, $to_username )); 
            $pmform->addElement(new XoopsFormHidden('to_userid', $to_userid));
        } else {
            $to_username = new XoopsFormSelectUser('', 'to_userid');
            $xoopsTpl->assign('to_username', $to_username->render());
        }
        $subject = "";
        $message = "";
    }
    $pmform->addElement(new XoopsFormText(_EPROFILE_MA_PMSUBJECTC, 'subject', 30, 100, $subject), true);
    $pmform->addElement(new XoopsFormDhtmlTextArea(_EPROFILE_MA_PMMESSAGEC, 'message', $message, 8, 37), true);
    $pmform->addElement(new XoopsFormRadioYN(_EPROFILE_MA_PMSAVEINOUTBOX, 'savecopy', 0));
    
    $pmform->addElement(new XoopsFormHidden('op', 'submit'));
    
    $submit = new XoopsFormElementTray("", "");
    $submit->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
    $cancel_send = new XoopsFormButton('', 'cancel', _EPROFILE_MA_PMCANCELSEND, 'button');
    $cancel_send->setExtra("onclick='javascript:window.location.href =\"viewpmsg.php\";'");
    $submit->addElement($cancel_send);
    $pmform->addElement($submit);
    $pmform->assign($xoopsTpl);
    //$xoopsTpl->display("db:profile_pmwrite.html");
}
include $GLOBALS['xoops']->path( '/footer.php' ); 
?>