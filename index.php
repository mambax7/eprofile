<?php
/**
 * Extended User Profile
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
 * @package         profile
 * @since           2.3.0
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @author          Dirk Herrmann <dhcst@users.sourceforge.net>
 * @version         $Id: index.php 2 2012-08-16 08:20:47Z alfred $
 */

include 'header.php';

if ( !$isOwner ) {
	if ($uid <= 1) {
		//redirect_header(XOOPS_URL . "/user.php", 2, _US_SELECTNG); //_US_SORRYNOTFOUND);
    $xoopsOption['template_main'] = 'profile_start.html';
    include $GLOBALS['xoops']->path('header.php');   
    $xoopsTpl->assign('eprofile_version','eProfile '.XoopsLocal::number_format(($xoopsModule->getVar('version') / 100)));
    include $GLOBALS['xoops']->path('footer.php');
    exit();
	}
	$thisUser =& $member_handler->getUser($uid);
  if ( !$thisUser ) {
		redirect_header(XOOPS_URL . "/user.php", 2, _US_SELECTNG);
	}
	if ( !$thisUser->isActive() || !isAdmin ) {
		redirect_header(XOOPS_URL . "/user.php", 2, _US_SELECTNG);
	}	
} else {
	$thisUser = $GLOBALS['xoopsUser'];
}

$op = ( isset($_REQUEST['op']) ) ? trim($_REQUEST['op']) : 'main';
if ( !in_array($op,array('main','login','logout','actv','delete')) ) $op = 'main';

if ($op == 'main') {
    if (!$GLOBALS['xoopsUser']) {
        $xoopsOption['template_main'] = 'system_userform.html';
        include $GLOBALS['xoops']->path('header.php');
        $GLOBALS['xoopsTpl']->assign('lang_login', _LOGIN);
        $GLOBALS['xoopsTpl']->assign('lang_username', _USERNAME);
        if (isset($_GET['xoops_redirect'])) {
            $GLOBALS['xoopsTpl']->assign('redirect_page', htmlspecialchars(trim($_GET['xoops_redirect']), ENT_QUOTES));
        }
        if ($GLOBALS['xoopsConfig']['usercookie']) {
            $GLOBALS['xoopsTpl']->assign('lang_rememberme', _US_REMEMBERME);
        }
        $GLOBALS['xoopsTpl']->assign('lang_password', _PASSWORD);
        $GLOBALS['xoopsTpl']->assign('lang_notregister', _US_NOTREGISTERED);
        $GLOBALS['xoopsTpl']->assign('lang_lostpassword', _US_LOSTPASSWORD);
        $GLOBALS['xoopsTpl']->assign('lang_noproblem', _US_NOPROBLEM);
        $GLOBALS['xoopsTpl']->assign('lang_youremail', _US_YOUREMAIL);
        $GLOBALS['xoopsTpl']->assign('lang_sendpassword', _US_SENDPASSWORD);
        $GLOBALS['xoopsTpl']->assign('mailpasswd_token', $GLOBALS['xoopsSecurity']->createToken());
        include dirname(__FILE__) . '/footer.php';
        exit();
    }
    if ( !empty($_GET['xoops_redirect']) ) {
        $redirect = trim($_GET['xoops_redirect']);
        $isExternal = false;
        if ($pos = strpos( $redirect, '://' )) {
            $xoopsLocation = substr( XOOPS_URL, strpos( XOOPS_URL, '://' ) + 3 );
            if ( strcasecmp(substr($redirect, $pos + 3, strlen($xoopsLocation)), $xoopsLocation) ) {
                $isExternal = true;
            }
        }
        if (!$isExternal) {
            header('Location: ' . $redirect);
            exit();
        }
    }
    header('Location: '.XOOPS_URL.'/modules/'.$GLOBALS['xoopsModule']->getVar('dirname').'/userinfo.php?uid=' . $GLOBALS['xoopsUser']->getVar('uid'));
    exit();
}

if ($op == 'login') {
    include_once XOOPS_ROOT_PATH . '/include/checklogin.php';
    exit();
}

if ($op == 'logout') {
    $message = '';
    $_SESSION = array();
    session_destroy();
    setcookie($GLOBALS['xoopsConfig']['usercookie'], 0, - 1, '/');
    setcookie($GLOBALS['xoopsConfig']['usercookie'], 0, -1, '/', XOOPS_COOKIE_DOMAIN, 0);
    // clear entry from online users table
    if (is_object($xoopsUser)) {
        $online_handler =& xoops_gethandler('online');
        $online_handler->destroy($GLOBALS['xoopsUser']->getVar('uid'));
    }
    $message = _US_LOGGEDOUT . '<br />' . _US_THANKYOUFORVISIT;
    redirect_header(XOOPS_URL . '/', 1, $message);
    exit();
}

if ($op == 'actv') {
    $id     = intval($_GET['id']);
    $actkey = trim($_GET['actkey']);
    redirect_header(XOOPS_URL."/modules/".$GLOBALS['xoopsModule']->getVar('dirname')."/activate.php?op=actv&amp;id={$id}&amp;actkey={$actkey}", 1, '');
}

if ($op == 'delete') {
    if (!$GLOBALS['xoopsUser'] || $GLOBALS['xoopsConfigUser']['self_delete'] != 1) {
        redirect_header(XOOPS_URL . '/', 5, _US_NOPERMISS);
    } else {
        $groups = $GLOBALS['xoopsUser']->getGroups();
        if (in_array(XOOPS_GROUP_ADMIN, $groups)){
            // users in the webmasters group may not be deleted
            redirect_header(XOOPS_URL . '/', 5, _US_ADMINNO);
        }
        $ok = !isset($_POST['ok']) ? 0 : intval($_POST['ok']);
        if ($ok != 1) {
            include $GLOBALS['xoops']->path('header.php');
            xoops_confirm(array('op' => 'delete', 'ok' => 1), 'user.php', _US_SURETODEL . '<br/>' . _US_REMOVEINFO);
            include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'footer.php';
        } else {
            $del_uid = $GLOBALS['xoopsUser']->getVar("uid");
            $member_handler =& xoops_gethandler('member');
            if (false != $member_handler->deleteUser($GLOBALS['xoopsUser'])) {
                $online_handler =& xoops_gethandler('online');
                $online_handler->destroy($del_uid);
                xoops_notification_deletebyuser($del_uid);
                redirect_header(XOOPS_URL . '/', 5, _US_BEENDELED);
            }
            redirect_header(XOOPS_URL . '/', 5, _US_NOPERMISS);
        }
    }
}
?>