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
 *  TinyMCE adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.3.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id: formtinymce.php 8066 2011-11-06 05:09:33Z beckmi $
 */
xoops_load('XoopsEditor');

/**
 * Class XoopsFormTinymce5Bootstrap
 */
class XoopsFormTinymce5Bootstrap extends XoopsEditor
{
    public $language;
    public $width = '100%';
    public $height = '500px';

    public $editor;

    /**
     * Constructor
     *
     * @param array $configs Editor Options
     */
    public function __construct($configs)
    {
        $current_path = __FILE__;
        if (DIRECTORY_SEPARATOR != '/') {
            $current_path = str_replace(mb_strpos($current_path, '\\\\', 2) ? '\\\\' : DIRECTORY_SEPARATOR, '/', $current_path);
        }

        $this->rootPath = '/class/xoopseditor/tinymce5bootstrap';
        parent::__construct($configs);
        $this->configs['elements'] = $this->getName();
        $this->configs['language'] = $this->getLanguage();
        $this->configs['rootpath'] = $this->rootPath;
        $this->configs['area_width'] = isset($this->configs['width']) ? $this->configs['width'] : $this->width;
        $this->configs['area_height'] = isset($this->configs['height']) ? $this->configs['height'] : $this->height;
        $this->configs['fonts'] = $this->getFonts();

        require_once __DIR__ . '/tinymce.php';
        $this->editor = new TinyMCE($this->configs);
    }

    /**
     * Renders the Javascript function needed for client-side for validation
     *
     * I'VE USED THIS EXAMPLE TO WRITE VALIDATION CODE
     * http://tinymce.moxiecode.com/punbb/viewtopic.php?id=12616
     *
     * @return string
     */
    public function renderValidationJS()
    {
        if ($this->isRequired() && $eltname = $this->getName()) {
            //$eltname = $this->getName();
            $eltcaption = $this->getCaption();
            $eltmsg = empty($eltcaption) ? sprintf(_FORM_ENTER, $eltname) : sprintf(_FORM_ENTER, $eltcaption);
            $eltmsg = str_replace('"', '\"', stripslashes($eltmsg));
            $ret = "\n";
            $ret .= "if ( tinyMCE.get('{$eltname}').getContent() == \"\" || tinyMCE.get('{$eltname}').getContent() == null) ";
            $ret .= "{ window.alert(\"{$eltmsg}\"); tinyMCE.get('{$eltname}').focus(); return false; }";

            return $ret;
        }

        return '';
    }

    /**
     * get language
     *
     * @return string
     */
    public function getLanguage()
    {
        if ($this->language) {
            return $this->language;
        }
        if (defined('_XOOPS_EDITOR_TINYMCE5B_LANGUAGE')) {
            $this->language = mb_strtolower(constant('_XOOPS_EDITOR_TINYMCE5B_LANGUAGE'));
        } else {
            $this->language = str_replace('_', '-', mb_strtolower(_LANGCODE));
            if ('utf-8' == mb_strtolower(_CHARSET)) {
                $this->language .= '_utf8';
            }
        }

        return $this->language;
    }

    /**
     * @return mixed
     */
    public function getFonts()
    {
        if (empty($this->config['fonts']) && defined('_XOOPS_EDITOR_TINYMCE5B_FONTS')) {
            $this->config['fonts'] = constant('_XOOPS_EDITOR_TINYMCE5B_FONTS');
        }

        return @$this->config['fonts'];
    }

    /**
     * prepare HTML for output
     *
     * @return sting HTML
     */
    public function render()
    {
        $ret = $this->editor->render();
        $ret .= parent::render();

        return $ret;
    }

    /**
     * Check if compatible
     *
     * @return bool
     */
    public function isActive()
    {
        return is_readable(XOOPS_ROOT_PATH . $this->rootPath . '/tinymce.php');
    }
}
