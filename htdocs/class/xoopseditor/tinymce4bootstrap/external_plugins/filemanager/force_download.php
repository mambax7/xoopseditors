<?php
include('config/config.php');
if('RESPONSIVEfilemanager' != $_SESSION['verify']) die('forbiden');
include('include/utils.php');

if(0 === strpos($_POST['path'], '/')
   || FALSE !== strpos($_POST['path'], '../')
   || 0 === strpos($_POST['path'], './'))
    die('wrong path');

if(FALSE !== strpos($_POST['name'], '/'))
    die('wrong path');

$path=$current_path.$_POST['path'];
$name=$_POST['name'];

$info=pathinfo($name);
if(!in_array(fix_strtolower($info['extension']), $ext)){
    die('wrong extension');
}

header('Pragma: private');
header('Cache-control: private, must-revalidate');
header('Content-Type: application/octet-stream');
header('Content-Length: ' . (string)(filesize($path . $name)) );
header('Content-Disposition: attachment; filename="'.($name).'"');
readfile($path.$name);

exit;
