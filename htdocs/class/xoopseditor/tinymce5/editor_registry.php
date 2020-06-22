<?php

/**
 *  TinyMCE adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.3.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id: editor_registry.php 8066 2011-11-06 05:09:33Z beckmi $
 */
return $config = [
    'name' => 'tinymce5',
    'class' => 'XoopsFormTinymce5',
    'file' => XOOPS_ROOT_PATH . '/class/xoopseditor/tinymce5/formtinymce.php',
    'title' => _XOOPS_EDITOR_TINYMCE5,
    'order' => 5,
    'nohtml' => 0,
];
