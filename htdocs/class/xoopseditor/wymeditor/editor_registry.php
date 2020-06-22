<?php
/**
 *  WYMeditor adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.3.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @author          luciorota <lucio.rtoa@gmail.com>
 */

return $config = [
    'name'   => 'wymeditor',
    'class'  => 'XoopsFormWYMeditor',
    'file'   => XOOPS_ROOT_PATH . '/class/xoopseditor/wymeditor/formwymeditor.php',
    'title'  => _XOOPS_EDITOR_WYMEDITOR,
    'order'  => 7,
    'nohtml' => 0
];

