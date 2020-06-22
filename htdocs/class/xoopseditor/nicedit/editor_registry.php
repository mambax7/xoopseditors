<?php
/**
 *  NicEdit adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.3.x
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @author          Rota Lucio <lucio.rota@gmail.com>
 * @author          Laurent JEN (aka DuGris)<dugris@afux.org>
 * @version         $Id: editor_registry.php 3496 2009-08-25 14:21:12Z dugris $
 */

return $config = [
    'name'   => 'nicedit',
    'class'  => 'XoopsFormNicedit',
    'file'   => XOOPS_ROOT_PATH . '/class/xoopseditor/nicedit/formnicedit.php',
    'title'  => _XOOPS_EDITOR_NICEDIT,
    'order'  => 9,
    'nohtml' => 0
];

