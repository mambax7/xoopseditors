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
 *  SCEditor adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.5.6
 * @author          Michael Beck <mambax7@gmail.com>
 * @version         $Id: formtinymce.php 8066 2011-11-06 05:09:33Z beckmi $
 */

xoops_load('XoopsEditor');

class XoopsFormSceditor extends XoopsEditor
{
    public $language = _LANGCODE;
    public $width;
    public $height;

    //    var $editor;

    public function __construct($configs)
    {
        $current_path = __FILE__;
        if (DIRECTORY_SEPARATOR != '/') {
            $current_path = str_replace(strpos($current_path, "\\\\", 2) ? "\\\\" : DIRECTORY_SEPARATOR, '/', $current_path);
        }
        $this->rootPath = '/class/xoopseditor/sceditor';
        parent::__construct($configs);
        $this->width  = $configs['width'];
        $this->height = $configs['height'];
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    /**
     * get textarea width
     *
     * @return  string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * get textarea height
     *
     * @return  string
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * get language
     *
     * @return  string
     */
    public function getLanguage()
    {
        return str_replace('_', '-', strtolower($this->language));
    }

    /**
     * set language
     *
     * @return  null
     */
    public function setLanguage($lang = 'en')
    {
        $this->language = $lang;
    }

    /**
     * Get initial content
     *
     * @param bool $encode To sanitizer the text? Default value should be "true"; however we have to set "false" for backward compat
     * @return        string
     */
    public function getValue()
    {
        return strtr(htmlspecialchars_decode($this->_value), ["\n" => '<br />', "\r\n" => '<br />']);
    }

    /**
     * Renders the Javascript function needed for client-side for validation
     *
     * @return    string
     */
    public function renderValidationJS()
    {
        if ($this->isRequired() && $eltname = $this->getName()) {
            $eltcaption = $this->getCaption();
            $eltmsg     = empty($eltcaption) ? sprintf(_FORM_ENTER, $eltname) : sprintf(_FORM_ENTER, $eltcaption);
            $eltmsg     = str_replace('"', '\"', stripslashes($eltmsg));
            $ret        = "\n";
            $ret        .= "if ( myform.{$eltname}.value == '' || myform.{$eltname}.value == '<br />' )";
            $ret        .= "{ window.alert(\"{$eltmsg}\"); myform.{$eltname}.focus(); return false; }";
            return $ret;
        }
        return '';
    }

    /**
     * prepare HTML for output
     *
     * @return  sting HTML
     */
    public function render()
    {
        static $isJsLoaded = false;
        $ret = "\n";
        if (!$isJsLoaded) {
            /* css files in header */
            $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/sceditor/sceditor/minified/themes/default.min.css', ['type' => 'text/css', 'media' => 'all']);
            /* js files in header */
            $GLOBALS['xoTheme']->addScript('browse.php?Frameworks/jquery/jquery.js');
            $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/sceditor/sceditor/minified/jquery.sceditor.min.js');
            $isJsLoaded = true;
        }

        $ret .= "<script type='text/javascript'>\n";
        $ret .= "jQuery(document).ready(function(){\n";
        //               $ret.= "      jQuery.sceditor.defaultOptions.width = 650;\n";
        $ret .= "   	jQuery.sceditor.defaultOptions.height = 250;\n";
        $ret .= "       jQuery('#" . $this->getName() . "').sceditorBBCodePlugin({style: '/minified/jquery.sceditor.default.min.css'});\n";
        $ret .= "   });\n";

        $ret .= "</script>\n";

        $ret .= "<textarea class='" . $this->getName() . "' name='" . $this->getName() . "' id='" . $this->getName() . "' " . $this->getExtra() . "style='width:" . $this->getWidth() . ';height:' . $this->getHeight() . ";'>" . $this->getValue() . '</textarea>';
        return $ret;
    }
}


