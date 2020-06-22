<?php
/**
 *  TinyMCE adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.3.0
 * @author          Laurent JEN <dugris@frxoops.org>
 * @version         $Id: xoopsimagemanager.php 6514 2011-04-01 22:07:23Z kris_fr $
 */

if (!defined('XOOPS_ROOT_PATH')) {
    die('XOOPS root path not defined');
}

// check categories readability by group
$groups         = is_object($GLOBALS['xoopsUser']) ? $GLOBALS['xoopsUser']->getGroups() : [XOOPS_GROUP_ANONYMOUS];
$imgcat_handler =& xoops_gethandler('imagecategory');
if (0 == count($imgcat_handler->getList($groups, 'imgcat_read', 1))) {
    return false;
}
return true;

