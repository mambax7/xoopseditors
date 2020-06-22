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

xoops_load('XoopsEditor');

class XoopsFormCodemirror3 extends XoopsEditor
{
    public $rootpath;
    public $config   = [];
    public $setting  = [];
    public $language = _LANGCODE;
    public $width    = '100%';
    public $height   = '300px';
    public $syntax   = 'txt'; // default
    public $mode     = null; // default

    /**
     * Constructor
     *
     * @param array $configs Editor Options
     */
    public function __construct($configs, $mode)
    {
        $this->rootPath = '/class/xoopseditor/codemirror3';
        parent::__construct($configs);
        $this->width  = isset($this->configs['width']) ? $this->configs['width'] : $this->width;
        $this->height = isset($this->configs['height']) ? $this->configs['height'] : $this->height;
        $this->syntax = isset($this->configs['syntax']) ? $this->configs['syntax'] : $this->syntax;
        $this->mode   = $mode;
    }

    public function setConfig($config)
    {
        foreach ($config as $key => $val) {
            $this->config[$key] = $val;
        }
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
     * Renders the Javascript function needed for client-side for validation
     *
     * @return    string
     */
    public function renderValidationJS()
    {
        if ($this->isRequired() && $eltname = $this->getName()) {
            //$eltname = $this->getName();
            $eltcaption = $this->getCaption();
            $eltmsg     = empty($eltcaption) ? sprintf(_FORM_ENTER, $eltname) : sprintf(_FORM_ENTER, $eltcaption);
            $eltmsg     = str_replace('"', '\"', stripslashes($eltmsg));
            $ret        = "\n";
            $ret        .= "if ( window.codemirror3_editor['{$eltname}'].getValue() == \"\" || window.codemirror3_editor['{$eltname}'].getValue() == null) ";
            $ret        .= "{ window.alert(\"{$eltmsg}\"); window.codemirror3_editor['{$eltname}'].focus(); return false; }";
            return $ret;
        }
        return '';
    }

    /**
     * Renders the Javascript function needed for client-side for get content
     *
     * @return    string
     */
    public function renderGetContentJS()
    {
        if ($eltname = $this->getName()) {
            $ret = "window.codemirror3_editor['{$eltname}'].getValue()";
            return $ret;
        }
        return '';
    }

    /**
     * get language
     *
     * @return    string
     */
    public function getLanguage()
    {
        if ($this->language) {
            return $this->language;
        }
        if (defined('_XOOPS_EDITOR_CODEMIRROR3_LANGUAGE')) {
            $this->language = strtolower(constant('_XOOPS_EDITOR_CODEMIRROR3_LANGUAGE'));
        } else {
            $this->language = str_replace('_', '-', strtolower(_LANGCODE));
        }

        return $this->language;
    }

    /**
     * Get initial content
     *
     * @param bool $encode To sanitizer the text? Default value should be "true"; however we have to set "false" for backward compat
     * @return        string
     */
    public function getValue()
    {
        //return strtr(htmlspecialchars_decode($this->_value) , array("\n" => '<br />', "\r\n" =>'<br />'));
        return $this->_value;
    }

    /**
     * prepare HTML for output
     *
     * @param bool    decode content?
     * @return  sting HTML
     */
    public function render($decode = true)
    {
        static $isCodemirror3JsLoaded;

        $ret = '';
        if (is_object($GLOBALS['xoopsModule'])) {
            $dirname = $GLOBALS['xoopsModule']->getVar('dirname');
        } else {
            $dirname = 'system';
        }

        // Load common stuff only once
        if (!$isCodemirror3JsLoaded) {
            // CodeMirror custom css
            $css = ".CodeMirror {border: none;}\n";
            $css .= ".CodeMirror {height: 100%;}\n";
            $css .= ".CodeMirror {width: 100%;}\n";
            $css .= ".CodeMirror-gutters {background-color: #F0F0EE;}\n";
            $css .= ".CodeMirror-scroll {overflow-y: hidden; overflow-x: auto;}\n";
            $css .= ".CodeMirror-activeline-background {background: #e8f2ff !important;}\n"; // Highlighting the current line
            $css .= ".cm-tab {background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAMCAYAAAAkuj5RAAAAAXNSR0IArs4c6QAAAGFJREFUSMft1LsRQFAQheHPowAKoACx3IgEKtaEHujDjORSgWTH/ZOdnZOcM/sgk/kFFWY0qV8foQwS4MKBCS3qR6ixBJvElOobYAtivseIE120FaowJPN75GMu8j/LfMwNjh4HUpwg4LUAAAAASUVORK5CYII=); background-position: right; background-repeat: no-repeat;}\n"; // Visible tabs
            $css .= ".CodeMirror-foldmarker {color: blue; text-shadow: #b9f 1px 1px 2px, #b9f -1px -1px 2px, #b9f 1px -1px 2px, #b9f -1px 1px 2px; font-family: arial;}\n";
            // Get available codemirror themes
            function codemirror_filesList($d, $x)
            {
                foreach (array_diff(scandir($d), ['.', '..']) as $f) {
                    if (is_file($d . '/' . $f) && (($x) ? preg_match('/' . $x . '$/', $f) : 1)) {
                        $l[] = $f;
                    }
                }
                return $l;
            }

            $themes = codemirror_filesList(XOOPS_ROOT_PATH . '/class/xoopseditor/codemirror3/codemirror/theme', '.css');
            // Generate no common html/javascript/stylesheet
            if (is_object($GLOBALS['xoTheme'])) {
                // uses $GLOBALS['xoTheme']
                // CodeMirror stuff
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/lib/codemirror.css');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/lib/codemirror.js');
                $GLOBALS['xoTheme']->addStylesheet(null, null, $css);
                // CodeMirror addons
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/addon/selection/active-line.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/addon/fold/foldcode.js');
                $GLOBALS['xoTheme']->addScript(null, null, 'var foldFunc = CodeMirror.newFoldFunction(CodeMirror.braceRangeFinder);');
                $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/addon/display/fullscreen.css');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/addon/display/fullscreen.js');
                // Automatic xml tag closing
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/addon/edit/closetag.js');
                // Automatic match brakets
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/addon/edit/matchbrackets.js');
                // CodeMirror themes
                foreach ($themes as $theme) {
                    $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/theme/' . $theme);
                }
                // CodeMirror modes
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/xml/xml.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/javascript/javascript.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/css/css.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/less/less.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/vbscript/vbscript.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/htmlmixed/htmlmixed.js');
                $GLOBALS['xoTheme']->addScript(
                    null,
                    null,
                    "var mixedMode = {name: 'htmlmixed', scriptTypes: [{matches: /\/x-handlebars-template|\/x-mustache/i, mode: null}, {matches: /(text|application)\/(x-)?vb(a|script)/i, mode: 'vbscript'}]};"
                ); // Define an extended mixed-mode that understands vbscript and leaves mustache/handlebars embedded templates in html mode
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/smarty/smarty.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/smartymixed/smartymixed.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/clike/clike.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/plsql/plsql.js');
                $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/mode/php/php.js');
                // Initialize arrays for multiple CodeMirror istances
                $GLOBALS['xoTheme']->addScript(null, null, 'var codemirror3_editor = new Array();');
                $GLOBALS['xoTheme']->addScript(null, null, 'var codemirror3_textarea = new Array();');
                $GLOBALS['xoTheme']->addScript(null, null, 'var codemirror3_uiOptions = new Array();');
                $GLOBALS['xoTheme']->addScript(null, null, 'var codemirror3_codeMirrorOptions = new Array();');
            } else {
                // does not use $GLOBALS['xoTheme']
                // CodeMirror stuff
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/lib/codemirror.css);</style>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/lib/codemirror.js' type='text/javascript'></script>\n";
                $ret .= "<style type='text/css'>\n" . $css . "</style>\n";
                // CodeMirror addons
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/addon/selection/active-line.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/addon/fold/foldcode.js' type='text/javascript'></script>\n";
                $ret .= "<script type='text/javascript'>var foldFunc = CodeMirror.newFoldFunction(CodeMirror.braceRangeFinder);</script>\n";
                $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/addon/display/fullscreen.css);</style>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/addon/display/fullscreen.js' type='text/javascript'></script>\n";
                // Automatic xml tag closing
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/addon/edit/closetag.js' type='text/javascript'></script>\n";
                // Automatic match brakets
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/addon/edit/matchbrackets.js' type='text/javascript'></script>\n";
                // CodeMirror themes
                foreach ($themes as $theme) {
                    $ret .= "<style type='text/css'>@import url(" . XOOPS_URL . '/class/xoopseditor/codemirror3/codemirror/theme/' . $theme . ");</style>\n";
                }
                // CodeMirror modes
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/xml/xml.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/javascript/javascript.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/css/css.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/less/less.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/vbscript/vbscript.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/htmlmixed/htmlmixed.js' type='text/javascript'></script>\n";
                $ret .= "<script type='text/javascript'>var mixedMode = {name: 'htmlmixed', scriptTypes: [{matches: /\/x-handlebars-template|\/x-mustache/i, mode: null}, {matches: /(text|application)\/(x-)?vb(a|script)/i, mode: 'vbscript'}]};</script>\n"; // Define an extended mixed-mode that understands vbscript and leaves mustache/handlebars embedded templates in html mode
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/smarty/smarty.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/smartymixed/smartymixed.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/clike/clike.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/plsql/plsql.js' type='text/javascript'></script>\n";
                $ret .= "<script src='" . XOOPS_URL . "/class/xoopseditor/codemirror3/codemirror/mode/php/php.js' type='text/javascript'></script>\n";
                // Initialize arrays for multiple CodeMirror istances
                $ret .= "<script type='text/javascript'>\n";
                $ret .= "var codemirror3_editor = new Array();\n";
                $ret .= "var codemirror3_textarea = new Array();\n";
                $ret .= "var codemirror3_uiOptions = new Array();\n";
                $ret .= "var codemirror3_codeMirrorOptions = new Array();\n";
                $ret .= "</script>\n";
            }
            $isCodemirror3JsLoaded = true;
        }

        // Set no common settings
        if ($decode) {
            $ts    = MyTextSanitizer::getInstance();
            $value = $ts->undoHtmlSpecialChars($this->getValue());
        } else {
            $value = $this->getValue();
        }
        $mode = (isset($this->mode) ? $this->mode : $this->syntax);
        switch ($mode) {
            case 'txt' :
            case 'text/plain' :
                $this->setting['mode'] = 'text/plain';
                break;
            case 'htm' :
            case 'html' :
            case 'htmlmixed' :
            case 'text/html' :
            case 'xhtml' :
            case 'application/xhtml+xml' :
                $this->setting['mode'] = 'text/html';
                break;
            case 'php' :
            case 'text/php' :
            case 'text/x-php' :
            case 'application/php' :
            case 'application/x-php' :
            case 'application/x-httpd-php' :
            case 'application/x-httpd-php-source' :
                $this->setting['mode'] = 'php';
                break;
            case 'css' :
            case 'text/css' :
                $this->setting['mode'] = 'css';
                break;
            case 'less' :
            case 'text/less' :
                $this->setting['mode'] = 'less';
                break;
            case 'js' :
            case 'javascript' :
            case 'text/javascript' :
            case 'text/ecmascript' :
            case 'application/javascript' :
            case 'application/ecmascript' :
            case 'application/x-javascript' :
            case 'application/json' :
            case 'text/typescript' :
            case 'application/typescript' :
                $this->setting['mode'] = 'javascript';
                break;
            case 'json' :
            case 'application/json' :
                $this->setting['mode'] = 'application/json';
                break;
            case 'smarty' :
            case 'smartymixed' :
            case 'text/x-smarty' :
                $this->setting['mode']           = 'smartymixed';//'smarty';
                $this->setting['smartyVersion']  = (int)3;
                $this->setting['leftDelimiter']  = '<{';
                $this->setting['rightDelimiter'] = '}>';
                break;
            case 'mysql' :
            case 'text/x-mysql' :
            case 'text/x-mariadb' :
            case 'sql' :
            case 'text/x-plsql' :
                $this->setting['mode'] = $mode;
                break;
            case 'xml' :
            case 'text/xml' :
            case 'application/xml' :
                $this->setting['mode'] = '{name: "xml", alignCDATA: true}';
                break;
            case 'csv' :
            case 'text/csv' :
                $this->setting['mode'] = 'text/plain';
                break;
            default :
                break;
        }
        $this->setting['theme']           = 'eclipse';//'default';
        $this->setting['lineNumbers']     = true;
        $this->setting['firstLineNumber'] = (int)1;
        $this->setting['lineNumbers']     = true;
        $this->setting['lineWrapping']    = true;
        $this->setting['matchBrackets']   = true;
        // ??? $this->setting['autoMatchParens'] = true;
        $this->setting['indentUnit']     = (int)4;
        $this->setting['indentWithTabs'] = false;
        $this->setting['enterMode']      = 'keep';// ???
        $this->setting['tabMode']        = 'indent';//'shift'; ???
        $this->setting['readOnly']       = isset($this->configs['readonly']) ? $this->configs['readonly'] : false;
        // Visible tabs
        $this->setting['tabSize']        = (int)4;
        $this->setting['indentUnit']     = (int)4;
        $this->setting['indentWithTabs'] = true;
        // Highlighting the current line
        $this->setting['styleActiveLine'] = true;
        // Automatic xml tag closing
        $this->setting['autoCloseTags'] = true;
        // Extrakeys
        $this->setting['extraKeys'] = [
            '"Ctrl-Q": function(cm){foldFunc(cm, cm.getCursor().line);}',
            '"F11": function(cm) {cm.setOption("fullScreen", !cm.getOption("fullScreen"));}',
            '"Esc": function(cm) {if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);}'
        ];

        // Generate no common editor html/javascript
        $ret .= "<div style='height:{$this->getHeight()};width:{$this->getWidth()};border:1px solid #CCCCCC;'>";
        $ret .= "<textarea name='" . $this->getName() . "' id='" . $this->getName() . "' rows='" . $this->getRows() . "' cols='" . $this->getCols() . "' " . $this->getExtra() . " style='width:" . $this->getWidth() . ';height:' . $this->getHeight() . ";'>" . $this->getValue() . "</textarea>\n";
        $ret .= '</div>';
        $ret .= '<small>' . _XOOPS_EDITOR_CODEMIRROR3_MODE . ' ' . $this->setting['mode'] . '</small>';
        $ret .= '<br />';
        $ret .= '<small>' . _XOOPS_EDITOR_CODEMIRROR3_FULLSCREEN . '</small>';
        $ret .= "<script type='text/javascript'>\n";
        $ret .= "window.codemirror3_editor['" . $this->getName() . "'] = CodeMirror.fromTextArea(document.getElementById('" . $this->getName() . "'), {";
        $ret .= "\n";
        foreach ($this->setting as $key => $val) {
            $ret .= $key . ': ';
            if (true === $val) {
                $ret .= 'true,';
            } elseif (false === $val) {
                $ret .= 'false,';
            } elseif (is_array($val)) {
                $ret .= '{';
                foreach ($val as $valkey => $valval) {
                    $val[$valkey] = '' . $valval . '';
                }
                $ret .= implode(',', $val);
                $ret .= '},';
            } elseif (is_int($val)) {
                $ret .= "{$val},";
            } else {
                $ret .= "'{$val}',";
            }
            $ret .= "\n";
        }
        $ret .= "});\n";
        $ret .= "codemirror3_editor['" . $this->getName() . "'].on('gutterClick', foldFunc);\n";
        $ret .= "</script>\n";
        return $ret;
    }
}
