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
 * Adapted Xinha wysiwyg editor
 * @copyright     copyright (c) 2000-2003 XOOPS.org
 *
 * @author        phppp
 * @author        luciorota (lucio.rota@gmail.com)
 */
class XoopsFormXinha extends XoopsFormTextArea
{
    public $language = _LANGCODE;
    public $caption;
    public $name;
    public $value;
    public $rows     = 5; // default
    public $cols     = 50; // default
    public $width    = '100%'; // default
    public $height   = '300px'; // default

    // COMPATIBLE WITH "formdhtmltextarea.php" HACK (Xoops 2.0.13+) AND WITH MODULES LIKE "News","Smartsection", ...
    /**
     * Constructor
     *
     * @param string $caption Caption
     * @param string $name    "name" attribute
     * @param string $value   Initial text
     * @param string $rows    Number of rows (facultative)
     * @param string $cols    Number of cols (facultative)
     * @param string $width   iframe width (facultative)
     * @param string $height  iframe height (facultative)
     * @param array  $options Toolbar Options (facultative)
     *                        OR
     * @param array  $options Editor Options
     */

    public function __construct()
    {
        if (func_num_args()) { // if there is/are one or more arguments...
            $numargs   = func_num_args(); // number of arguments
            $args_list = func_get_args(); // is an array of arguments
            if ((!empty($args_list[$numargs - 1])) && (is_array($args_list[$numargs - 1]))) { // if the last argument is an array...
                $options = $args_list[$numargs - 1]; // ... it is an array of options
                foreach ($options as $key => $val) { // it sets the options
                    if (method_exists($this, 'set' . Ucfirst($key))) {
                        $this->{'set' . Ucfirst($key)}($val);
                    } else {
                        $this->$key = $val;
                    }
                }
            }
            if ($numargs >= 2) {
                $this->caption = $args_list[0];
                $this->name    = $args_list[1];
                $this->value   = (($numargs >= 3) ? $args_list[2] : '');
                $this->rows    = (($numargs >= 4) ? $args_list[3] : $rows);
                $this->cols    = (($numargs >= 5) ? $args_list[4] : $cols);
                $this->XoopsFormTextArea($this->caption, $this->name, $this->value, $this->rows, $this->cols);
            }
        }
    }

    public function XoopsFormXinha()
    {
        $this->__construct();
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
        static $isXinhaJsLoaded = false;
        $ret = '';
        if (!$isXinhaJsLoaded) {
            $ret .= "<script type='text/javascript'>\n";
            // You must set _editor_url to the URL (including trailing slash) where
            // where xinha is installed, it's highly recommended to use an absolute URL
            //  eg: _editor_url = "/path/to/xinha/";
            // You may try a relative URL if you wish]
            //  eg: _editor_url = "../";
            $ret .= "_editor_url  = '" . XOOPS_URL . "/class/xoopseditor/xinha/';\n";
            // And the language we need to use in the editor.
            $ret .= "_editor_lang = '" . $this->getLanguage() . "';\n";
            // If you want use a skin, add the name (of the folder) here
            // Original skins are: blue-look, blue-metallic, green-look, inditreuse, silva, titan, xp-blue, xp-green
            $ret .= "_editor_skin = 'blue_look';\n";
            $ret .= "</script>\n";
            //$ret .= "<script type='text/javascript' src='" . XOOPS_URL . "/class/xoopseditor/xinha/XinhaLoader.js'></script>\n";
            $ret .= "<script type='text/javascript' src='" . XOOPS_URL . "/class/xoopseditor/xinha/XinhaCore.js'></script>\n";

            $ret             .= <<<EOF
<script type='text/javascript'>
var xinha_editors = new Array;
xinha_init = null;
xinha_config = null;
xinha_plugins = null;

// This contains the names of textareas we will make into Xinha editors
xinha_init = xinha_init ? xinha_init : function()
{
    /** STEP 1 ***************************************************************
     * First, what are the plugins you will be using in the editors on this page.
     * List all the plugins you will need, even if not all the editors will use all the plugins.
     ************************************************************************/
    // Xinha plugins
    // Abbreviation, CharCounter, ClientsideSpellcheck, DoubleClick, EditTag, Equation,
    // ExtendedFileManager, FindReplace, FormOperations, Forms, GetHtml, HorizontalRule?,
    // HtmlEntities, ImageManager, InsertAnchor, InsertMarquee, InsertSmiley, InsertWords,
    // LangMarks, Linker, NoteServer, QuickTag, Stylist, SaveSubmit, SmartReplace, SuperClean,
    // Template, UnFormat?
    // htmlArea plugins
    // CharacterMap, ContextMenu, CSS, DynamicCSS, EnterParagraphs, FullPage?, HtmlTidy,
    // ListType, SpellChecker, TableOperations 
    xinha_plugins = xinha_plugins ? xinha_plugins :	[
    'ExtendedFileManager',
    'CharacterMap',
    'ContextMenu',
    'ListType',
    'Stylist',
    'Linker',
    'SuperClean',
    'TableOperations',
    'ImageManager'
    ];
    // THIS BIT OF JAVASCRIPT LOADS THE PLUGINS, NO TOUCHING  :)
    if(!Xinha.loadPlugins(xinha_plugins, xinha_init)) return;

    /** STEP 2 ***************************************************************
     * Now, what are the names of the textareas you will be turning into editors?
     ************************************************************************/
    //xinha_editors = xinha_editors ? xinha_editors : ['myTextArea'];

    /** STEP 3 ***************************************************************
     * We create a default configuration to be used by all the editors.
     * If you wish to configure some of the editors differently this will be
     * done in step 4.
     *
     * If you want to modify the default config you might do something like this.
     *
     *   xinha_config = new Xinha.Config();
     *   xinha_config.width  = 640;
     *   xinha_config.height = 420;
     *
    *************************************************************************/
    xinha_config = xinha_config ? xinha_config() : new Xinha.Config();
    // true : will retrieve the full HTML, starting with the <HTML> tag.
    // false : retrieve only the body content.
    xinha_config.fullPage = false;
    // Set to true if you want the loading panel to show at startup.
    xinha_config.showLoading = true;
    // Set to false if you want to allow JavaScript in the content, otherwise <script> tags are stripped out.
    xinha_config.stripScripts = true;
    xinha_config.statusBar = true;
    xinha_config.stripBaseHref = true;
    xinha_config.CharacterMap.mode = 'panel';
    // This property controls the height of the editor.
    // Allowed values are 'auto' or a numeric value followed by px.
    // auto : let Xinha choose the height to use.
    // numeric value : forced height in pixels ('200px').
    // default value : 'auto'
    xinha_config.height = 'auto';
    // This property controls the width of the editor.
    // Allowed values are 'auto', 'toolbar' or a numeric value followed by px.
    // auto : let Xinha choose the width to use.
    // toolbar : compute the width size from the toolbar width.
    // numeric value : forced width in pixels ('600px').
    // default value : 'toolbar'
    xinha_config.width = 'toolbar';
    // We can load an external stylesheet like this - NOTE : YOU MUST GIVE AN ABSOLUTE URL
    //  otherwise it won't work!
    // xinha_config.stylistLoadStylesheet(document.location.href.replace(/[^\/]*\.html/, 'stylist.css'));
    // Or we can load styles directly
    //xinha_config.stylistLoadStyles('p.red_text { color:red }');
    // If you want to provide "friendly" names you can do so like
    // (you can do this for stylistLoadStylesheet as well)
    //xinha_config.stylistLoadStyles('p.pink_text { color:pink }', {'p.pink_text' : 'Pretty Pink'});
    xinha_config.pageStyleSheets = [ _editor_url + 'examples/full_example.css' ];
    // When the editor is in different directory depth as the edited page relative image sources will break the display of your images.
    // This fixes an issue where Mozilla converts the urls of images and links that are on the same server to relative ones (../) when dragging them around in the editor (Ticket #448) Allowed values are true or false.
    // true : if you want to have relative URLs in links an images converted to absolute ones.
    // false : no update done to the baseHref (absolute links).
    xinha_config.expandRelativeUrl = true;
    // This controls the method that is used to retrieve the actual HTML from the editor.
    // At the moment there are two alternative methods ( = possible values) available:
    // "DOMwalk" : traverses through the document structure to extract tags, attributes, text nodes, etc.
    // "TransformInnerHTML" : grabs the innerHTML value and transforms it with Regular Expressions to get well formed XHTML.
    xinha_config.getHtmlMethod = 'TransformInnerHTML';
    // Enable the 'Target' field in the Make Link dialog. The TARGET property is invalid with XHTML document.
    // Allowed values are true or false.
    // true : enable the 'Target' field in the Make Link dialog.
    // false : disable the 'Target' field in the Make Link dialog.
    xinha_config.makeLinkShowsTarget = true;
    // Sometimes we want to be able to replace some string in the html coming in and going out,
    // so that in the editor we use the "internal" string, and outside and in the source view we
    // use the "external" string this is useful for say making special codes for your absolute links,
    // your external string might be some special code, say "{server_url}" an you say that the
    // internal representation of that should be http://your.server/
    //xinha_config.specialReplacements = { 'http://www.myserver.com/' : '{server_url}' };

    /** STEP 4 ***************************************************************
    * We first create editors for the textareas.
    *
    * You can do this in two ways, either
    *
    *   xinha_editors   = Xinha.makeEditors(xinha_editors, xinha_config, xinha_plugins);
    *
    * if you want all the editor objects to use the same set of plugins, OR;
    *
    *   xinha_editors = Xinha.makeEditors(xinha_editors, xinha_config);
    *   xinha_editors.myTextArea.registerPlugins(['Stylist']);
    *   xinha_editors.anotherOne.registerPlugins(['CSS','SuperClean']);
    *
    * if you want to use a different set of plugins for one or more of the editors.
    ************************************************************************/
    xinha_editors = Xinha.makeEditors(xinha_editors, xinha_config, xinha_plugins);

    /** STEP 5 ***************************************************************
    * If you want to change the configuration variables of any of the
    * editors,  this is the place to do that, for example you might want to
    * change the width and height of one of the editors, like this...
    *
    *   xinha_editors.myTextArea.config.width  = '640px';
    *   xinha_editors.myTextArea.config.height = '480px';
    *
    ************************************************************************/

    /** STEP 6 ***************************************************************
    * Finally we 'start' the editors, this turns the textareas into
    * Xinha editors.
    ************************************************************************/
    Xinha.startEditors(xinha_editors);
    }

// this executes the xinha_init function on page load 
// and does not interfere with window.onload properties set by other scripts
Xinha._addEvent(window,'load', xinha_init);
</script>
EOF;
            $isXinhaJsLoaded = true;
        }
        $ret .= "<style type='text/css'>.htmlarea .toolbar table { width:auto;}</style>\n";
        $ret .= "<textarea name='"
                . $this->getName()
                . "' id='"
                . $this->getName()
                . "' rows='"
                . $this->getRows()
                . "' cols='"
                . $this->getCols()
                . "' "
                . $this->getExtra()
                . " style='width:"
                . $this->getWidth()
                . ';height:'
                . $this->getHeight()
                . ";display:none;'>"
                . $this->getValue()
                . "</textarea>\n";
        $ret .= "<script type='text/javascript'>xinha_editors.push('" . $this->getName() . "');</script>\n";
        return $ret;
    }
}


