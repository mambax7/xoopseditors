<?php
/**
 * Adapted JWysiwyg editor
 *
 * @copyright         The XOOPS project http://www.xoops.org/
 * @license           http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package           core
 * @subpackage        xoopseditor
 * @since             2.4.5
 * @author            kris <http://www.xoofoo.org>
 */

xoops_load('XoopsEditor');

/**
 * Constructor
 *
 * @param string $caption Caption
 * @param string $name    "name" attribute
 * @param string $value   Initial text
 * @param string $width   iframe width
 * @param string $height  iframe height
 * @param array  $options Toolbar Options
 */
class XoopsFormJwysiwyg extends XoopsEditor
{
    public $language = _LANGCODE;
    public $width;
    public $height;

    // PHP 5 Constructor
    public function __construct($configs)
    {
        $current_path = __FILE__;
        if (DIRECTORY_SEPARATOR != '/') {
            $current_path = str_replace(strpos($current_path, "\\\\", 2) ? "\\\\" : DIRECTORY_SEPARATOR, '/', $current_path);
        }
        $this->rootPath = '/class/xoopseditor/jwysiwyg';
        parent::__construct($configs);
        $this->width  = $configs['width'];
        $this->height = $configs['height'];
    }

    // PHP 4 Constructor
    public function XoopsFormJwysiwyg($configs)
    {
        $this->__construct($configs);
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
            $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/jwysiwyg/jwysiwyg/jquery.wysiwyg.css');
            $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/jwysiwyg/jwysiwyg/jquery.wysiwyg.modal.css');
            $GLOBALS['xoTheme']->addStylesheet(XOOPS_URL . '/class/xoopseditor/jwysiwyg/lib/jquery.simplemodal.css');
            /* js files in header */
            $GLOBALS['xoTheme']->addScript('browse.php?Frameworks/jquery/jquery.js');
            //$GLOBALS['xoTheme']->addScript('browse.php?Frameworks/jquery/plugins/jquery.ui.js');
            $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/jwysiwyg/jwysiwyg/jquery.wysiwyg.js');
            $GLOBALS['xoTheme']->addScript(XOOPS_URL . '/class/xoopseditor/jwysiwyg/lib/jquery.simplemodal.js');
        }
        $ret .= "<textarea class='" . $this->getName() . " jwysiwyg' name='" . $this->getName() . "' id='" . $this->getName() . "'" . $this->getExtra() . "style='width:" . $this->getWidth() . ';height:' . $this->getHeight() . ";'>" . $this->getValue() . "</textarea>\n";
        $ret .= "<script type='text/javascript'>\n";
        $ret .= "   jQuery('#" . $this->getName() . "').wysiwyg();\n";
        $ret .= "</script>\n";
        return $ret;
    }
}


