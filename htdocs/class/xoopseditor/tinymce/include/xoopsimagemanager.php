<?php
/**
 *  TinyMCE adapter for XOOPS
 *
 * @copyright       (c) 2000-2014 XOOPS Project (www.xoops.org)
 * @license             http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package             class
 * @subpackage          editor
 * @since               2.3.0
 * @author              Laurent JEN <dugris@frxoops.org>
 * @version             $Id: xoopsimagemanager.php 12666 2014-06-30 10:02:07Z beckmi $
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
