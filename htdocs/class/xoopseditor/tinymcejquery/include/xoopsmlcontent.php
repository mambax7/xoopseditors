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
 * @version         $Id: xoopsmlcontent.php 6514 2011-04-01 22:07:23Z kris_fr $
 */

if (!defined("XOOPS_ROOT_PATH")) { die("XOOPS root path not defined"); }

// Xlanguage
if ( $GLOBALS["module_handler"]->getByDirname("xlanguage") && defined("XLANGUAGE_LANG_TAG") ) {
    return true;
}

// Easiest Multi-Language Hack (EMLH)
if ( defined('EASIESTML_LANGS') && defined('EASIESTML_LANGNAMES') ) {
    return true;
}

return false;
?>