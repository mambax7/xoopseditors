<?php
/**
 *  Xinha v0.95 adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.3.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id: editor_registry.php 1573 2008-05-04 15:24:06Z phppp $
 */

return $config = [
    'name'   => 'xinha',
    'class'  => 'XoopsFormXinha',
    'file'   => XOOPS_ROOT_PATH . '/class/xoopseditor/xinha/formxinha.php',
    'title'  => _XOOPS_EDITOR_XINHA,
    'order'  => 8,
    'nohtml' => 0
];

