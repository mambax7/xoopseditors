<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 *  FCKeditor adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.3.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id: editor_registry.php 5454 2010-10-08 09:13:16Z kris_fr $
 */

return $config = array(
        "class"     =>  "XoopsFormFckeditor",
        "file"      =>  XOOPS_ROOT_PATH . "/class/xoopseditor/fckeditor/formfckeditor.php",
        "title"     =>  _XOOPS_EDITOR_FCKEDITOR,
        "order"     =>  3,
        "nohtml"    =>  0
    );
?>