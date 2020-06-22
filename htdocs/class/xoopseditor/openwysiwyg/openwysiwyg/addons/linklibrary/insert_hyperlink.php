<?php
/********************************************************************
 * openImageLibrary addon Copyright (c) 2006 openWebWare.com
 * Contact us at devs@openwebware.com
 * This copyright notice MUST stay intact for use.
 ********************************************************************/

require('config.inc.php');
error_reporting(0);
// get the identifier of the editor
$wysiwyg = $_GET['wysiwyg'];
// set image dir
$leadon = $rootdir . $linkbasedir;

if ('.' == $leadon) {
    $leadon = '';
}
if (('/' != substr($leadon, -1, 1)) && '' != $leadon) {
    $leadon = $leadon . '/';
}
$startdir = $leadon;

// validate the directory
$_GET['dir'] = $_POST['dir'] ? $_POST['dir'] : $_GET['dir'];
if ($_GET['dir']) {
    if ('/' != substr($_GET['dir'], -1, 1)) {
        $_GET['dir'] = $_GET['dir'] . '/';
    }
    $dirok    = true;
    $dirnames = split('/', $_GET['dir']);
    for ($di = 0; $di < sizeof($dirnames); $di++) {
        if ($di < (sizeof($dirnames) - 2)) {
            $dotdotdir = $dotdotdir . $dirnames[$di] . '/';
        }
    }
    if ('/' == substr($_GET['dir'], 0, 1)) {
        $dirok = false;
    }

    if ($_GET['dir'] == $leadon) {
        $dirok = false;
    }

    if ($dirok) {
        $leadon = $_GET['dir'];
    }
}

// upload file
if ($allowuploads && $_FILES['file']) {
    $upload = true;
    if (!$overwrite) {
        if (file_exists($leadon . $_FILES['file']['name'])) {
            $upload = false;
        }
    }
    $ext = strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1));
    if (!in_array($ext, $supportedextentions)) {
        $upload = false;
    }
    if ($upload) {
        move_uploaded_file($_FILES['file']['tmp_name'], $leadon . $_FILES['file']['name']);
    }
}

if ($allowuploads) {
    $phpallowuploads = (bool)ini_get('file_uploads');
    $phpmaxsize      = ini_get('upload_max_filesize');
    $phpmaxsize      = trim($phpmaxsize);
    $last            = strtolower($phpmaxsize{strlen($phpmaxsize) - 1});
    switch ($last) {
        case 'g':
            $phpmaxsize *= 1024;
        case 'm':
            $phpmaxsize *= 1024;
    }
}

?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
    <title>openWYSIWYG | Create or Modify Link</title>

    <script type="text/javascript" src="../../scripts/wysiwyg-popup.js"></script>
    <script language="JavaScript" type="text/javascript">

        /* ---------------------------------------------------------------------- *\
          Function    : insertHyperLink() (changed)
          Description : Insert the link into the iframe html area
        \* ---------------------------------------------------------------------- */
        function insertHyperLink() {
            var n = WYSIWYG_Popup.getParam('wysiwyg');

            // get values from form fields
            var href = document.getElementById('linkUrl').value;
            var target = document.getElementById('linkTarget').value;
            var style = document.getElementById('linkStyle').value;
            var styleClass = document.getElementById('linkClass').value;
            var name = document.getElementById('linkName').value;

            // insert link
            WYSIWYG.insertLink(href, target, style, styleClass, name, n);
            window.close();
        }

        /* ---------------------------------------------------------------------- *\
          Function    : loadLink() (new)
          Description : Load the link attributes to the form
        \* ---------------------------------------------------------------------- */
        function loadLink() {
            // get params
            var n = WYSIWYG_Popup.getParam('wysiwyg');

            // get selection and range
            var sel = WYSIWYG.getSelection(n);
            var range = WYSIWYG.getRange(sel);
            var lin = null;
            if (WYSIWYG_Core.isMSIE) {
                if (sel.type == "Control" && range.length == 1) {
                    range = WYSIWYG.getTextRange(range(0));
                    range.select();
                }
                if (sel.type == 'Text' || sel.type == 'None') {
                    sel = WYSIWYG.getSelection(n);
                    range = WYSIWYG.getRange(sel);
                    // find a as parent element
                    lin = WYSIWYG.findParent("a", range);
                }
            } else {
                // find a as parent element
                lin = WYSIWYG.findParent("a", range);
            }

            // if no link as parent found exit here
            if (lin == null) return;

            // set form elements with attribute values
            for (var i = 0; i < lin.attributes.length; i++) {
                var attr = lin.attributes[i].name.toLowerCase();
                var value = lin.attributes[i].value;
                if (attr && value && value != "null") {
                    switch (attr) {
                        case "href":
                            // strip off urls on IE
                            if (WYSIWYG_Core.isMSIE) value = WYSIWYG.stripURLPath(n, value, false);
                            document.getElementById('linkUrl').value = value;
                            break;
                        case "target":
                            document.getElementById('linkTarget').value = value;
                            selectItemByValue(document.getElementById('linkTargetChooser'), value);
                            break;
                        case "name":
                            document.getElementById('linkName').value = value;
                            break;
                        case "class":
                            document.getElementById('linkClass').value = value;
                            break;
                        case "className":
                            document.getElementById('linkClass').value = value;
                            break;

                    }
                }
            }

            // Getting style attribute of the link separately, because IE interprets the
            // style attribute is an complex object, and do not return a text stylesheet like Mozilla.
            document.getElementById('linkStyle').value = WYSIWYG_Core.replaceRGBWithHexColor(WYSIWYG_Core.getAttribute(lin, "style"));
        }

        /* ---------------------------------------------------------------------- *\
          Function    : updateTarget() (new)
          Description : Updates the target text field
          Arguments   : value - Value to be set
        \* ---------------------------------------------------------------------- */
        function updateTarget(value) {
            document.getElementById('linkTarget').value = value;
        }

        /* ---------------------------------------------------------------------- *\
          Function    : selectItem()
          Description : Select an item of an select box element by value.
        \* ---------------------------------------------------------------------- */
        function selectItemByValue(element, value) {
            if (element.options.length) {
                for (var i = 0; i < element.options.length; i++) {
                    if (element.options[i].value == value) {
                        element.options[i].selected = true;
                        return;
                    }
                }
                element.options[(element.options.length - 1)].selected = true;
            }
        }

    </script>
</head>
<body bgcolor="#EEEEEE" marginwidth="0" marginheight="0" topmargin="0" leftmargin="0" onLoad="loadLink();">
<table border="0" cellpadding="0" cellspacing="0" style="padding: 10px;">
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?wysiwyg=<?php echo $wysiwyg; ?>" enctype="multipart/form-data">
        <input type="hidden" id="dir" name="dir" value="">
        <tr>
            <td style="vertical-align:top;">
                <span style="font-family: arial, verdana, helvetica; font-size: 11px; font-weight: bold;">Insert Hyperlink:</span>
                <table width="380" border="0" cellpadding="1" cellspacing="0" style="background-color: #F7F7F7; border: 2px solid #FFFFFF; padding: 5px;">
                    <tr>
                        <td style="padding-bottom: 2px; padding-top: 0px; font-family: arial, verdana, helvetica; font-size: 11px;">URL:</td>
                        <td style="padding-bottom: 2px; padding-top: 0px;" colspan="3">
                            <input type="text" name="linkUrl" id="linkUrl" value="http://" style="font-size: 10px; width: 100%;">
                        </td>
                    </tr>
                </table>

                <span style="font-family: arial, verdana, helvetica; font-size: 11px; font-weight: bold;">Upload File:</span>
                <table width="380" border="0" cellpadding="0" cellspacing="0" style="background-color: #F7F7F7; border: 2px solid #FFFFFF; padding: 5px;">
                    <?php
                    if ($allowuploads) {
                        if ($phpallowuploads) {
                            ?>
                            <tr>
                                <td style="padding-top: 0px;padding-bottom: 0px; font-family: arial, verdana, helvetica; font-size: 11px;width:80px;">Upload:</td>
                                <td style="padding-top: 0px;padding-bottom: 0px;width:300px;"><input type="file" name="file" size="30" style="font-size: 10px; width: 100%;"/></td>
                            </tr>
                            <tr>
                                <td style="padding-top: 0px;padding-bottom: 2px;font-family: tahoma; font-size: 9px;">&nbsp;</td>
                                <td style="padding-top: 0px;padding-bottom: 2px;font-family: tahoma; font-size: 9px;">(Max Filesize: <?php echo $phpmaxsize; ?>KB)</td>
                            </tr>
                            <?php
                        } else {
                            ?>
                            <tr>
                                <td style="padding-bottom: 2px; padding-top: 0px; font-family: arial, verdana, helvetica; font-size: 11px;" colspan="2">
                                    File uploads are disabled in your php.ini file. Please enable them.
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </table>

                <span style="font-family: arial, verdana, helvetica; font-size: 11px; font-weight: bold;">Link Options:</span>
                <table width="380" border="0" cellpadding="1" cellspacing="0" style="background-color: #F7F7F7; border: 2px solid #FFFFFF; padding: 5px;">
                    <tr>
                        <td style="padding-bottom: 2px; width: 50px; font-family: arial, verdana, helvetica; font-size: 11px;">Target:</td>
                        <td style="padding-bottom: 2px;" colspan="3">
                            <input type="text" name="linkTarget" id="linkTarget" value="" style="font-size: 10px; width: 65%;">
                            &nbsp;
                            <select name="linkTargetChooser" id="linkTargetChooser" style="font-size: 10px; width: 30%;" onchange="updateTarget(this.value);">
                                <option value="" selected>no target</option>
                                <option value="_blank">_blank</option>
                                <option value="_self">_self</option>
                                <option value="_parent">_parent</option>
                                <option value="_top">_top</option>
                                <option value="">custom</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 2px; width: 50px; font-family: arial, verdana, helvetica; font-size: 11px;">Style:</td>
                        <td style="padding-bottom: 2px;" colspan="3">
                            <input type="text" name="linkStyle" id="linkStyle" value="" style="font-size: 10px; width: 100%;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 2px; width: 50px; font-family: arial, verdana, helvetica; font-size: 11px;">Class:</td>
                        <td style="padding-bottom: 2px;">
                            <input type="text" name="linkClass" id="linkClass" value="" style="font-size: 10px; width: 90%;">
                        </td>
                        <td style="padding-bottom: 2px; width: 30px; font-family: arial, verdana, helvetica; font-size: 11px;">Name:</td>
                        <td style="padding-bottom: 2px; width: 120px;">
                            <input type="text" name="linkName" id="linkName" value="" style="font-size: 10px; width: 100%;">
                        </td>
                    </tr>

                </table>
            </td>
            <td style="vertical-align: top;width: 150px; padding-left: 5px;">
                <span style="font-family: arial, verdana, helvetica; font-size: 11px; font-weight: bold;">Select File:</span>
                <iframe id="chooser" frameborder="0" style="height:165px;width: 180px;border: 2px solid #FFFFFF; padding: 5px;" src="select_file.php?dir=<?php echo $leadon; ?>"></iframe>
            </td>
        </tr>
        <tr>
            <td colspan="2" align="right" style="padding-top: 5px;">
                <input type="submit" value="  Submit  " onclick="insertHyperLink();return false;" style="font-size: 12px;">
                <?php if ($allowuploads) { ?>
                    <input type="submit" value="  Upload  " style="font-size: 12px;">
                <?php } ?>
                <input type="button" value="  Cancel  " onclick="window.close();" style="font-size: 12px;">
            </td>
        </tr>
    </form>
</table>
</body>
</html>
