<?php

/********************************************************************
 * openImageLibrary addon Copyright (c) 2006 openWebWare.com
 * Contact us at devs@openwebware.com
 * This copyright notice MUST stay intact for use.
 ********************************************************************/
require('config.inc.php');
error_reporting(0);
if (('/' != substr($linkbaseurl, -1, 1)) && '' != $linkbaseurl) {
    $linkbaseurl = $linkbaseurl . '/';
}
if (('/' != substr($linkbasedir, -1, 1)) && '' != $linkbasedir) {
    $linkbasedir = $linkbasedir . '/';
}
$leadon = $linkbasedir;
if ('.' == $leadon) {
    $leadon = '';
}
if (('/' != substr($leadon, -1, 1)) && '' != $leadon) {
    $leadon = $leadon . '/';
}
$startdir = $leadon;

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

$opendir = $leadon;
if (!$leadon) {
    $opendir = '.';
}
if (!file_exists($opendir)) {
    $opendir = '.';
    $leadon  = $startdir;
}

clearstatcache();
if ($handle = opendir($opendir)) {
    while (false !== ($file = readdir($handle))) {
        //first see if this file is required in the listing
        if ('.' == $file || '..' == $file) {
            continue;
        }
        if ('dir' == @filetype($leadon . $file)) {
            if (!$browsedirs) {
                continue;
            }

            $n++;
            if ('date' == $_GET['sort']) {
                $key = @filemtime($leadon . $file) . ".$n";
            } else {
                $key = $n;
            }
            $dirs[$key] = $file . '/';
        } else {
            $n++;
            if ('date' == $_GET['sort']) {
                $key = @filemtime($leadon . $file) . ".$n";
            } elseif ('size' == $_GET['sort']) {
                $key = @filesize($leadon . $file) . ".$n";
            } else {
                $key = $n;
            }
            $files[$key] = $file;
        }
    }
    closedir($handle);
}

//sort our files
if ('date' == $_GET['sort']) {
    @ksort($dirs, SORT_NUMERIC);
    @ksort($files, SORT_NUMERIC);
} elseif ('size' == $_GET['sort']) {
    @natcasesort($dirs);
    @ksort($files, SORT_NUMERIC);
} else {
    @natcasesort($dirs);
    @natcasesort($files);
}

//order correctly
if ('desc' == $_GET['order'] && 'size' != $_GET['sort']) {
    $dirs = @array_reverse($dirs);
}
if ('desc' == $_GET['order']) {
    $files = @array_reverse($files);
}
$dirs  = @array_values($dirs);
$files = @array_values($files);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
    <title>openWYSIWYG | Select File</title>
    <style type="text/css">
        body {
            margin: 0px;
        }

        a {
            font-family: Arial, verdana, helvetica;
            font-size: 11px;
            color: #000000;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
    <script type="text/javascript">
        function selectLink(url) {
            if (parent) {
                parent.document.getElementById("linkUrl").value = url;
            }
        }

        if (parent) {
            parent.document.getElementById("dir").value = '<?php echo $leadon; ?>';
        }

    </script>
</head>
<body>
<table border="0">
    <tbody>
    <?php
    $breadcrumbs = split('/', str_replace($basedir . '/', '', $leadon));
    if (($bsize = sizeof($breadcrumbs)) > 0) {
        if (($bsize - 1) > 0) {
            echo '<tr><td>';
            $sofar = '';
            for ($bi = 0; $bi < ($bsize - 1); $bi++) {
                $sofar = $sofar . $breadcrumbs[$bi] . '/';
                echo '<a href="' . $_SERVER['PHP_SELF'] . '?dir=' . urlencode($sofar) . '" style="font-size:10px;font-family:Tahoma;">&raquo; ' . $breadcrumbs[$bi] . '</a><br>';
            }
            echo '</td></tr>';
        }
    }
    ?>
    <tr>
        <td>
            <?php
            $class = 'b';
            if ($dirok) {
                ?>
                <a href="<?php echo $_SERVER['PHP_SELF'] . '?dir=' . urlencode($dotdotdir); ?>"><img src="images/dirup.png" alt="Folder" border="0"/> <strong>..</strong></a><br>
                <?php
                if ('b' == $class) {
                    $class = 'w';
                } else {
                    $class = 'b';
                }
            }
            $arsize = sizeof($dirs);
            for ($i = 0; $i < $arsize; $i++) {
                $dir = substr($dirs[$i], 0, strlen($dirs[$i]) - 1);
                ?>
                <a href="<?php echo $_SERVER['PHP_SELF'] . '?dir=' . urlencode($leadon . $dirs[$i]); ?>"><img src="images/folder.png" alt="<?php echo $dir; ?>" border="0"/> <strong><?php echo $dir; ?></strong></a><br>
                <?php
                if ('b' == $class) {
                    $class = 'w';
                } else {
                    $class = 'b';
                }
            }

            $arsize = sizeof($files);
            for ($i = 0; $i < $arsize; $i++) {
                $icon = 'unknown.png';
                $ext  = strtolower(substr($files[$i], strrpos($files[$i], '.') + 1));
                if (in_array($ext, $supportedextentions)) {
                    $thumb = '';
                    if ($filetypes[$ext]) {
                        $icon = $filetypes[$ext];
                    }

                    $filename = $files[$i];
                    if (strlen($filename) > 43) {
                        $filename = substr($files[$i], 0, 40) . '...';
                    }
                    $fileurl = $leadon . $files[$i];
                    $filedir = str_replace($linkbasedir, '', $leadon);
                    ?>
                    <nobr><a href="javascript:void(0)" onclick="selectLink('<?php echo $linkbaseurl . $filedir . $filename; ?>');"><img src="images/<?php echo $icon; ?>" alt="<?php echo $files[$i]; ?>" border="0"/> <strong><?php echo $filename; ?></strong></a></nobr><br>
                    <?php
                    if ('b' == $class) {
                        $class = 'w';
                    } else {
                        $class = 'b';
                    }
                }
            }
            ?>
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>
