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

class XoopsFormAloha extends XoopsEditor
{
    var $language = _LANGCODE;
    var $width;
    var $height;
//    var $editor;

    function __construct($configs)
    {
        $current_path = __FILE__;
        if ( DIRECTORY_SEPARATOR != "/" ) {
            $current_path = str_replace( strpos( $current_path, "\\\\", 2 ) ? "\\\\" : DIRECTORY_SEPARATOR, "/", $current_path);
        }
        $this->rootPath = "/class/xoopseditor/aloha";
        parent::__construct($configs);
        $this->width = $configs['width'];
        $this->height = $configs['height'];
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
     * @return  string
     */
    function getLanguage()
    {
        return str_replace('_','-',strtolower($this->language));
    }
    /**
     * set language
     *
     * @return  null
     */
    function setLanguage($lang='en')
    {
        $this->language = $lang;
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
     * @return  sting HTML
     */
    function render()
    {
        static $isJsLoaded = false;
        $ret = "\n";
        if(!$isJsLoaded)
        {
		/* css files in header */
		$GLOBALS['xoTheme']->addStylesheet( XOOPS_URL . '/class/xoopseditor/aloha/aloha/css/aloha.css', array('type'=>'text/css', 'media'=>'all') );
		/* js files in header */
		$GLOBALS['xoTheme']->addScript('rowse.php?Frameworks/jquery/jquery.js');
        $GLOBALS['xoTheme']->addScript( XOOPS_URL . '/class/xoopseditor/aloha/aloha/lib/require.js' );
        $GLOBALS['xoTheme']->addScript( XOOPS_URL . '/class/xoopseditor/aloha/aloha/lib/aloha.js', array('data-aloha-plugins'=>'common/ui,
        								common/format, common/characterpicker,common/horizontalruler,
        								common/align,
        		                        common/table,
        		                        common/list,
        		                        common/link,
        		                        common/highlighteditables,
        		                        common/block,
        		                        common/image,
        		                        common/undo,
        		                        common/contenthandler,
        		                        common/paste,
        		                        common/commands,
        		                        common/abbr'));
        $isJsLoaded = true;
        }


        $ret.= "<script type='text/javascript'>\n";
               $ret.= "Aloha.ready(function(){\n";
//               $ret.= "      jQuery.aloha.defaultOptions.width = 650;\n";
//               $ret.= "   	jQuery.aloha.defaultOptions.height = 250;\n";
                $ret.= "       Aloha.jQuery('#".$this->getName()."').aloha();\n";
               $ret.= "   });\n";

               $ret.= "</script>\n";

       	$ret.= "<textarea class='".$this->getName()."' name='".$this->getName()."' id='".$this->getName()."' ".$this->getExtra()."style='width:".$this->getWidth().";height:".$this->getHeight().";'>" . $this->getValue() . "</textarea>";
               return $ret ;

    }
}
?>