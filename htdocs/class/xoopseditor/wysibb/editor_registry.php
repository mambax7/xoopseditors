<?php
/**
 *  SCEditor adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.5.6
 * @author          Michael Beck <mambax7@gmail.com>
 * @version         $Id: editor_registry.php 8066 2011-11-06 05:09:33Z beckmi $
 */

return $config = array(
        "name"      =>  "sceditor",
        "class"     =>  "XoopsFormSceditor",
        "file"      =>  XOOPS_ROOT_PATH . "/class/xoopseditor/sceditor/formsceditor.php",
        "title"     =>  'SCEditor',
        "order"     =>  6,
        "nohtml"    =>  0
    );
?>
