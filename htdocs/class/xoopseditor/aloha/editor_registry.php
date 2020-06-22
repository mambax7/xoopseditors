<?php
/**
 *  Aloha adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.5.6
 * @author          Michael Beck <mambax7@gmail.com>
 * @version         $Id: editor_registry.php 8066 2011-11-06 05:09:33Z beckmi $
 */

return $config = [
    'name'   => 'aloha',
    'class'  => 'XoopsFormAloha',
    'file'   => XOOPS_ROOT_PATH . '/class/xoopseditor/aloha/formaloha.php',
    'title'  => 'Aloha',
    'order'  => 8,
    'nohtml' => 0
];

