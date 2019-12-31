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
 *  FCKeditor adapter for XOOPS
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         class
 * @subpackage      editor
 * @since           2.3.0
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @version         $Id: formfckeditor.php 5454 2010-10-08 09:13:16Z kris_fr $
 */

xoops_load('XoopsEditor');

class XoopsFormFckeditor extends XoopsEditor
{
    var $language;
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
        $this->rootPath = "/class/xoopseditor/fckeditor";
        parent::__construct($configs);
        $this->width = isset($this->configs["width"]) ? $this->configs["width"] : $this->width;
        $this->height = isset($this->configs["height"]) ? $this->configs["height"] : $this->height;
        $this->upload = isset($this->configs["upload"]) ? $this->configs["upload"] : $this->upload;
        $this->toolbarset = isset($this->configs["toolbarset"]) ? $this->configs["toolbarset"] : $this->toolbarset;
    }

    function XoopsFormFckeditor($configs)
    {
        $this->__construct($configs);
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
        if (defined("_XOOPS_EDITOR_FCKEDITOR_LANGUAGE")) {
            $this->language = strtolower(constant("_XOOPS_EDITOR_FCKEDITOR_LANGUAGE"));
        } else {
            $this->language = str_replace('_', '-', strtolower(_LANGCODE));
        }

        return $this->language;
    }

    /**
     * prepare HTML for output
     *
     * @param   bool    decode content?
     * @return  sting HTML
     */
    function render($decode = true)
    {
        $ret = '';
        $oFCKeditor = new FCKeditor($this->getName());
        $oFCKeditor->BasePath    = XOOPS_URL . $this->rootPath. "/fckeditor/";
        $oFCKeditor->ToolbarSet    = $this->toolbarset;
        $oFCKeditor->Width        = $this->width;
        $oFCKeditor->Height        = $this->height;
	$oFCKeditor->Config['EditorAreaCSS'] = xoops_getcss(); // add by kris
        if ($decode) {
            $ts = MyTextSanitizer::getInstance();
            $oFCKeditor->Value = $ts->undoHtmlSpecialChars( $this->getValue() );
        } else {
            $oFCKeditor->Value = $this->getValue();
        }

        if (is_readable(XOOPS_ROOT_PATH . $this->rootPath. '/fckeditor/editor/lang/' . $this->getLanguage() . '.js')) {
            $oFCKeditor->Config['DefaultLanguage'] = $this->getLanguage();
        }

        if (defined("_XOOPS_EDITOR_FCKEDITOR_FONTLIST")) {
            $oFCKeditor->Config['FontNames'] = _XOOPS_EDITOR_FCKEDITOR_FONTLIST;
        }

        $dirname = is_object($GLOBALS["xoopsModule"]) ? $GLOBALS["xoopsModule"]->getVar("dirname", "n") : "system";

        // Add for ImageManager plugin
        $_SESSION['module_dir'] = $dirname;

        if (!file_exists($config_file = XOOPS_ROOT_PATH . "/cache/fckconfig.{$dirname}.js")) {
            if ( $fp = fopen( $config_file , "wt" ) ) {
                $fp_content = "";
                if ($xoopsconfig = implode("", file(XOOPS_ROOT_PATH . $this->rootPath. '/fckconfig-xoops.js'))) {
                    $fp_content .= "/* FCKconfig custom configuration */\n";
                    $fp_content .= $xoopsconfig . "\n\n";
                }
                $fp_content .= "/* FCKconfig module configuration */\n";
                if (is_readable($config_mod = XOOPS_ROOT_PATH . "/modules/{$dirname}/fckeditor.config.js")) {
                    $fp_content .= "/* Loaded from module local config file */\n" . implode("", file($config_mod)) . "\n\n";
                }
                if (is_readable(XOOPS_ROOT_PATH . "/modules/{$dirname}/fckeditor.connector.php")) {
                    $fp_content .= "var browser_path = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Connector=" . XOOPS_URL . "/modules/" . $GLOBALS["xoopsModule"]->getVar("dirname", "n") . "/fckeditor.connector.php';\n";
                    $fp_content .= "FCKConfig.LinkBrowserURL = browser_path ;\n";
                    $fp_content .= "FCKConfig.ImageBrowserURL = browser_path + '&Type=Image';\n";
                    $fp_content .= "FCKConfig.FlashBrowserURL = browser_path + '&Type=Flash';\n\n";
                }
                if (is_readable(XOOPS_ROOT_PATH . "/modules/{$dirname}/fckeditor.upload.php")) {
                    $fp_content .= "var uploader_path = '" . XOOPS_URL . "/modules/{$dirname}/fckeditor.upload.php';\n";
                    $fp_content .= "FCKConfig.LinkUploadURL = uploader_path;\n";
                    $fp_content .= "FCKConfig.ImageUploadURL = uploader_path + '?Type=Image';\n";
                    $fp_content .= "FCKConfig.FlashUploadURL = uploader_path + '?Type=Flash';\n\n";
                }
                if (empty($this->upload)) {
                    $fp_content .= "FCKConfig.LinkUpload = false;\n";
                    $fp_content .= "FCKConfig.ImageUpload = false;\n";
                    $fp_content .= "FCKConfig.FlashUpload = false;\n\n";
                }

                fwrite( $fp, $fp_content );
                fclose( $fp );
            } else {
                trigger_error( "Cannot create fckeditor config file", E_USER_ERROR );
            }
        }

        if (is_readable($config_file)) {
            $oFCKeditor->Config['CustomConfigurationsPath'] = XOOPS_URL . "/cache/fckconfig.{$dirname}.js";
        } else {
            $oFCKeditor->Config['CustomConfigurationsPath'] = XOOPS_URL . $this->rootPath. '/fckconfig-xoops.js';
        }

        foreach ($this->configs as $key => $val) {
            if (isset($this->{$key})) continue;
            $oFCKeditor->Config[$key] = $val;
        }

        $ret = $oFCKeditor->CreateHtml();
        return $ret;
    }

    /**
     * Check if compatible
     *
     * @return
     */
    function isActive()
    {
        if ( ! @include_once XOOPS_ROOT_PATH . $this->rootPath. "/fckeditor/fckeditor.php" ) {
            $this->isEnabled = false;
        } else {
            $this->isEnabled = FCKeditor::IsCompatible();
        }
        return $this->isEnabled;
    }
}
?>