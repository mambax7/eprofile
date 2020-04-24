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
 * @version         $Id: visibility.php 2 2012-08-16 08:20:47Z alfred $
 */
include 'admin_header.php';
xoops_cp_header();

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : "visibility";
if ( !in_array($op,array('edit', 'search', 'access', 'visibility', 'del')) ) $op = "visibility";

include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

if ($op != "del") {
	$opform = new XoopsSimpleForm('', 'opform', 'admin_permissions.php', "get");
	$op_select = new XoopsFormSelect("", 'op', $op);
	$op_select->setExtra('onchange="document.forms.opform.submit()"');
	$op_select->addOption('visibility', _EPROFILE_AM_PROF_VISIBLE);
	$op_select->addOption('edit', _EPROFILE_AM_PROF_EDITABLE);
	$op_select->addOption('search', _EPROFILE_AM_PROF_SEARCH);
	$op_select->addOption('access', _EPROFILE_AM_PROF_ACCESS);
	$opform->addElement($op_select);
	$opform->display();
}

$visibility_handler = xoops_getmodulehandler('visibility');
$field_handler =& xoops_getmodulehandler('field');
$fields = $field_handler->getList();

if (isset($_REQUEST['submit'])) {
    $visibility = $visibility_handler->create();
    $visibility->setVar('field_id', $_REQUEST['field_id']);
    $visibility->setVar('user_group', $_REQUEST['ug']);
    $visibility->setVar('profile_group', $_REQUEST['pg']);
    $visibility_handler->insert($visibility, true);
}
if ($op == "del") {
    $criteria = new CriteriaCompo(new Criteria('field_id', intval($_REQUEST['field_id'])));
    $criteria->add(new Criteria('user_group', intval($_REQUEST['ug'])));
    $criteria->add(new Criteria('profile_group', intval($_REQUEST['pg'])));
    $visibility_handler->deleteAll($criteria, true);
    redirect_header("admin_visibility.php", 2, sprintf(_EPROFILE_AM_DELETEDSUCCESS, _EPROFILE_AM_PROF_VISIBLE));
    exit();
}

$criteria = new CriteriaCompo();
$criteria->setGroupby("field_id, user_group, profile_group");
$visibilities = $visibility_handler->getAll($criteria);

$member_handler = xoops_gethandler('member');
$groups = $member_handler->getGroupList();
$groups[0] = _EPROFILE_AM_FIELDVISIBLETOALL;
asort($groups);

$xoopsTpl->assign('fields', $fields);
$xoopsTpl->assign('visibilities', $visibilities);
$xoopsTpl->assign('groups', $groups);

$add_form = new XoopsSimpleForm('', 'addform', 'admin_visibility.php');

$sel_field = new XoopsFormSelect(_EPROFILE_AM_FIELDVISIBLE, 'field_id');
$sel_field->setExtra("style='width: 200px;'");
$sel_field->addOptionArray($fields);
$add_form->addElement($sel_field);

$sel_ug = new XoopsFormSelect(_EPROFILE_AM_FIELDVISIBLEFOR, 'ug');
$sel_ug->addOptionArray($groups);
$add_form->addElement($sel_ug);

unset($groups[XOOPS_GROUP_ANONYMOUS]);
$sel_pg = new XoopsFormSelect(_EPROFILE_AM_FIELDVISIBLEON, 'pg');
$sel_pg->addOptionArray($groups);
$add_form->addElement($sel_pg);

$add_form->addElement(new XoopsFormButton('', 'submit', _ADD, 'submit'));
$add_form->assign($xoopsTpl);

$xoopsTpl->display("db:profile_admin_visibility.html");

xoops_cp_footer();
?>