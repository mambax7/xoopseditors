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
 *  CodeMirror2 adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.4.x
 * @author          Rota Lucio <lucio.rota@gmail.com>
 * @version         $Id$
 */

xoops_load('XoopsEditor');

class XoopsFormCodemirror2 extends XoopsEditor
{
    var $rootpath;
    var $config = array();
    var $setting = array();
    var $language = _LANGCODE;
    var $width = '100%';
    var $height = '500px';
    var $syntax = 'html';
    
    /**
     * Constructor
     *
     * @param    array   $configs  Editor Options
     */
    function __construct($configs)
    {
        $this->rootPath = "/class/xoopseditor/codemirror2";
        parent::__construct($configs);
        $this->width = isset($this->configs["width"]) ? $this->configs["width"] : $this->width;
        $this->height = isset($this->configs["height"]) ? $this->configs["height"] : $this->height;
        $this->syntax = isset($this->configs["syntax"]) ? $this->configs["syntax"] : $this->syntax;
    }

    function XoopsFormCodemirror2($configs)
    {
        $this->__construct($configs);
    }

    function setConfig( $config )
    {
        foreach ($config as $key => $val) {
            $this->config[$key] = $val;
        }
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
        if (defined("_XOOPS_EDITOR_CODEMIRROR2_LANGUAGE")) {
            $this->language = strtolower(constant("_XOOPS_EDITOR_CODEMIRROR2_LANGUAGE"));
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
        //return strtr(htmlspecialchars_decode($this->_value) , array("\n" => '<br />', "\r\n" =>'<br />'));
        return $this->_value;
    }

    /**
     * Get initial content
     *
     * @param        bool    $encode To sanitizer the text? Default value should be "true"; however we have to set "false" for backward compat
     * @return        string
     */
    function renderGetValueJS() {
        return "codemirror2_editor[&quot;" . $this->getName() . "&quot;].mirror.getValue()";
    }
    
    /**
     * prepare HTML for output
     *
     * @param   bool    decode content?
     * @return  sting HTML
     */
    function render($decode = true)
    {
        static $isCodemirror2JsLoaded, $ret, $retb;
        
        $ret = '';
        $retb = '';
        if ( is_object($GLOBALS['xoopsModule']) ) 
            $dirname = $GLOBALS['xoopsModule']->getVar('dirname');
        else
            $dirname = 'system';
        if ( is_object($GLOBALS['xoTheme']) ) {
            if ( !$isCodemirror2JsLoaded ) {
                // CodeMirror stuff
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/lib/codemirror.css');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/lib/codemirror.js');
                // CodeMirror themes
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/theme/default.css');
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/theme/neat.css');
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/theme/elegant.css');
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/theme/night.css');
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/theme/cobalt.css');
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/theme/eclipse.css');
                // CodeMirror modes
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/mode/xml/xml.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/mode/javascript/javascript.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/mode/css/css.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/mode/htmlmixed/htmlmixed.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/mode/clike/clike.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/mode/plsql/plsql.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror2/CodeMirror/mode/php/php.js');
                // CodeMirrorUI stuff
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror2/codemirror-ui/js/codemirror-ui.js');
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror2/codemirror-ui/css/codemirror-ui.css');

                $GLOBALS['xoTheme']->addScript(null, null, 'var codemirror2_editor = new Array();');
                $GLOBALS['xoTheme']->addScript(null, null, 'var codemirror2_textarea = new Array();');
                $GLOBALS['xoTheme']->addScript(null, null, 'var codemirror2_uiOptions = new Array();');
                $GLOBALS['xoTheme']->addScript(null, null, 'var codemirror2_codeMirrorOptions = new Array();');

                /*
                if ( file_exists( XOOPS_ROOT_PATH . $this->rootPath . '/module/config. '. $dirname . '.js' ) ) 
                    $GLOBALS['xoTheme']->addScript( XOOPS_URL . $this->rootPath . '/module/config.' . $dirname . '.js' );            
                else
                    $GLOBALS['xoTheme']->addScript( XOOPS_URL . $this->rootPath . '/codemirror2/config.js' );
                */
                $isCodemirror2JsLoaded = true;
            }
        } else {
            if ( !$isCodemirror2JsLoaded ) {
                // CodeMirror stuff
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/lib/codemirror.css);</style>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/lib/codemirror.js' type='text/javascript'></script>\n";
                // CodeMirror themes
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/theme/default.css);</style>\n";
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/theme/neat.css);</style>\n";
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/theme/elegant.css);</style>\n";
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/theme/night.css);</style>\n";
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/theme/cobalt.css);</style>\n";
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/theme/eclipse.css);</style>\n";
                // CodeMirror modes
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/mode/xml/xml.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/mode/javascript/javascript.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/mode/css/css.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/mode/htmlmixed/htmlmixed.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/mode/clike/clike.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/mode/plsql/plsql.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror2/CodeMirror/mode/php/php.js' type='text/javascript'></script>\n";
                // CodeMirrorUI stuff
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror2/codemirror-ui/js/codemirror-ui.js' type='text/javascript'></script>\n";
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror2/codemirror-ui/css/codemirror-ui.css);</style>\n";

                $ret.= "<script type='text/javascript'>\n";
                $ret.= "var codemirror2_editor = new Array();\n";
                $ret.= "var codemirror2_textarea = new Array();\n";
                $ret.= "var codemirror2_uiOptions = new Array();\n";
                $ret.= "var codemirror2_codeMirrorOptions = new Array();\n";
                $ret.= "</script>\n";
                /*
                if ( file_exists( XOOPS_ROOT_PATH . $this->rootPath . '/module/config.' . $dirname . '.js' ) ) 
                    $ret .= '<script src="' . XOOPS_URL . $this->rootPath . '/module/config .'. $dirname . '.js' . '" type="text/javascript"></script>';
                else                
                    $ret .= '<script src="' . XOOPS_URL . $this->rootPath . '/codemirror2/config.js' . '" type="text/javascript"></script>';
                */
                $isCodemirror2JsLoaded = true;
            }
        }
        if ($decode) {
            $ts = MyTextSanitizer::getInstance();
            $value = $ts->undoHtmlSpecialChars( $this->getValue() );
        } else {
            $value = $this->getValue();
        }


/*
        $this->setting['path'] = XOOPS_URL . $this->rootPath . '/CodeMirror/lib/';
        $this->setting['height'] = $this->height;
        $this->setting['language'] = $this->language;
        //$this->setting['width'] = $this->width; DO NOT SET width in this way...
        $this->setting['tabMode'] = 'shift';

        
        define('_CODEMIRROR_CSS_PATH', XOOPS_URL . $this->rootPath . '/CodeMirror/css/');

*/

    switch ($this->syntax) {
        case 'css' :
            $this->setting['mode'] = 'text/css'; break;
        case 'js' :
            $this->setting['mode'] = 'text/javascript'; break;
        case 'html' :
            $this->setting['mode'] = 'text/html'; break;
        case 'json' :
            $this->setting['mode'] = 'application/json'; break;
        case 'php' :
            $this->setting['mode'] = 'application/x-httpd-php';  break;
        case 'sql' :
            $this->setting['mode'] = 'text/x-plsql';  break;
        default :
            break;
        }
    $this->setting['theme'] = 'default';
    $this->setting['lineNumbers'] = true;
    $this->setting['matchBrackets'] = true;
    // ??? $this->setting['autoMatchParens'] = true;
    $this->setting['indentUnit'] = 4;
    $this->setting['indentWithTabs'] = false;
    $this->setting['enterMode'] = 'keep';
    $this->setting['tabMode'] = 'shift';
    $this->setting['readOnly'] = isset($this->configs["readonly"]) ? $this->configs["readonly"] : false;

    $ret.= "<style type='text/css'>\n";
    $ret.= ".CodeMirror {border-top: 1px solid black; border-bottom: 1px solid black;}\n";
    $ret.= "</style>\n";

    $ret.= "<textarea name='" . $this->getName() . "' id='" . $this->getName() . "' rows='" . $this->getRows() . "' cols='" . $this->getCols() . "' " . $this->getExtra() . " style='width:" . $this->getWidth() . ";height:" . $this->getHeight() . ";'>" . $this->getValue() . "</textarea>\n";

    $ret.= "<script type='text/javascript'>\n";
    $ret.= "codemirror2_textarea['" . $this->getName() . "'] = document.getElementById('" . $this->getName() . "');\n";
    //first set up uiOptions options/settings
    $ret.= "codemirror2_uiOptions['" . $this->getName() . "'] = {\n";
    $ret.= "path: '" . XOOPS_URL . "/class/xoopseditor/codemirror2/codemirror-ui/js/',\n";
    $ret.= "searchMode: 'inline',\n";
    $ret.= "buttons : ['undo','redo','jump','reindent','about'],\n";
    $ret.= "};\n";
    //first set up codeMirror options/settings
    $ret.= "codemirror2_codeMirrorOptions['" . $this->getName() . "'] = {\n";
    // render codeMirror options/settings
    foreach ($this->setting as $key => $val) {
        $ret .= $key . ":";
        if ($val === true) {
            $ret.= "true,";
        } elseif ($val === false) {
            $ret .= "false,";
        } elseif (is_array($val)) {
            $ret .= "[";
            foreach ($val as $valkey => $valval) $val[$valkey] = "'" . $valval . "'";
            $ret .= implode(',', $val);
            $ret .= "],";
        } else {
            $ret .= "'{$val}',";
        }
        $ret .= "\n";
    }
    $ret.= "};\n";
    //then create the editor
    $ret.= "codemirror2_editor['" . $this->getName() . "'] = new CodeMirrorUI(codemirror2_textarea['" . $this->getName() . "'], codemirror2_uiOptions['" . $this->getName() . "'], codemirror2_codeMirrorOptions['" . $this->getName() . "']);\n";
    $ret.= "</script>\n";
    return $ret . $retb;
    }
}
?>