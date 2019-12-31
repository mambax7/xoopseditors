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
 *  NicEdit adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.4.x
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @author          Rota Lucio <lucio.rota@gmail.com>
 * @author          Laurent JEN (aka DuGris)<dugris@afux.org>
 * @version         $Id: formnicedit.php 3447 2009-08-13 18:00:17Z dugris $
 */

xoops_load('XoopsEditor');

class XoopsFormNicedit extends XoopsEditor
{
    var $language = _LANGCODE;
    var $upload = true;
    var $width = "100%";
    var $height = "500px";
    var $toolbarset = "Xoops";

    /**
     * Constructor
     *
     * @param    array   $configs  Editor Options
     */
    function __construct($configs)
    {
        $this->rootPath = "/class/xoopseditor/nicedit";
        parent::__construct($configs);
        $this->width = isset($this->configs["width"]) ? $this->configs["width"] : $this->width;
        $this->height = isset($this->configs["height"]) ? $this->configs["height"] : $this->height;
        $this->upload = isset($this->configs["upload"]) ? $this->configs["upload"] : $this->upload;
        $this->toolbarset = isset($this->configs["toolbarset"]) ? $this->configs["toolbarset"] : $this->toolbarset;
    }

    function XoopsFormNicedit($configs)
    {
        $this->__construct($configs);
    }

    function getName()
    {
        return $this->name;
    }

    function setName($value)
    {
        $this->name = $value;
    }

    /**
     * get textarea width
     *
     * @return  string
     */
    function getWidth()
    {
        return $this->width;
    }

    /**
     * get textarea height
     *
     * @return  string
     */
    function getHeight()
    {
        return $this->height;
    }

    /**
     * get language
     *
     * @return    string
     */
    function getLanguage()
    {
        if ($this->language) {
            return $this->language;
        }
        if (defined("_XOOPS_EDITOR_NICEDIT_LANGUAGE")) {
            $this->language = strtolower(constant("_XOOPS_EDITOR_NICEDIT_LANGUAGE"));
        } else {
            $this->language = str_replace('_', '-', strtolower(_LANGCODE));
        }

        return $this->language;
    }

    /**
     * Get initial content
     *
     * @param        bool    $encode To sanitizer the text? Default value should be "true"; however we have to set "false" for backward compat
     * @return        string
     */
    function getValue() {
        return strtr(htmlspecialchars_decode($this->_value) , array("\n" => '<br />', "\r\n" =>'<br />'));
    }

    /**
     * Renders the Javascript function needed for client-side for validation
     *
     * @return    string
     */
    function renderValidationJS()
    {
        if ($this->isRequired() && $eltname = $this->getName()) {
            //$eltname = $this->getName();
            $eltcaption = $this->getCaption();
            $eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
            $eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
            $ret = "\n";
            $ret.= "if ( myform.{$eltname}.value == '' || myform.{$eltname}.value == '<br />' )";
            $ret.= "{ window.alert(\"{$eltmsg}\"); myform.{$eltname}.focus(); return false; }";
            return $ret;
            }
        return '';
    }

    /**
     * prepare HTML for output
     *
     * @param   bool    decode content?
     * @return  sting HTML
     */
    function render($decode = true)
    {
        static $isJsLoaded = false;
        $ret = "\n";
        if(!$isJsLoaded)
        {
            $ret.= "<script type='text/javascript' src='" . XOOPS_URL . "/class/xoopseditor/nicedit/nicedit/nicEdit.js'></script>\n";
        }
        $ret.= "<script type='text/javascript'>\n";
        $ret.= "    bkLib.onDomLoaded(function() {\n";
        $ret.= "        new nicEditor({fullPanel : true, iconsPath : '" . XOOPS_URL . $this->rootPath . "/nicedit/nicEditorIcons.gif'}).panelInstance('" . $this->getName(). "');\n";
        $ret.= "    });\n";
        $ret.= "</script>\n";
        $ret.= "<textarea class='".$this->getName()."' name='".$this->getName()."' id='".$this->getName()."' rows='".$this->getRows()."' cols='".$this->getCols()."' ".$this->getExtra()." style='width:".$this->getWidth().";height:".$this->getHeight()."'>" . $this->getValue() . "</textarea>\n";
        return $ret;
    }
}
?>