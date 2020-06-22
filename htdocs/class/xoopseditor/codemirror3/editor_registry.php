<?php
/**
 *  CodeMirror3 adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.5.7
 * @author          Rota Lucio <lucio.rota@gmail.com>
 * @version         $Id$
 */

return $config = [
    'name'   => 'codemirror3',
    'class'  => 'XoopsFormCodemirror3',
    'file'   => XOOPS_ROOT_PATH . '/class/xoopseditor/codemirror3/formcodemirror3.php',
    'title'  => _XOOPS_EDITOR_CODEMIRROR3,
    'order'  => 20,
    'nohtml' => true,
    'modes'  => [
        'txt',
        'text/plain',
        'htm',
        'html',
        'htmlmixed',
        'text/html',
        'xhtml',
        'application/xhtml+xml',
        'php',
        'text/php',
        'text/x-php',
        'application/php',
        'application/x-php',
        'application/x-httpd-php',
        'application/x-httpd-php-source',
        'css',
        'text/css',
        'less',
        'text/x-less',
        'js',
        'javascript',
        'text/javascript',
        'text/ecmascript',
        'application/javascript',
        'application/ecmascript',
        'application/x-javascript',
        'application/json',
        'text/typescript',
        'application/typescript',
        'json',
        'application/json',
        'smarty',
        'text/x-smarty',
        'mysql',
        'text/x-mysql',
        'text/x-mariadb',
        'sql',
        'text/x-plsql',
        'xml',
        'text/xml',
        'application/xml',
        'csv',
        'text/csv'
    ]
];
