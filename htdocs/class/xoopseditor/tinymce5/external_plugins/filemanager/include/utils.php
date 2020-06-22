<?php

if ('RESPONSIVEfilemanager' !== $_SESSION['verify']) {
    die('forbiden');
}

/**
 * @param $dir
 * @return bool
 */
function deleteDir($dir)
{
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ('.' === $item || '..' === $item) {
            continue;
        }
        if (!deleteDir($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

/**
 * @param $old_path
 * @param $name
 * @return bool
 */
function duplicate_file($old_path, $name)
{
    if (file_exists($old_path)) {
        $info = pathinfo($old_path);
        $new_path = $info['dirname'] . '/' . $name . '.' . $info['extension'];
        if (file_exists($new_path)) {
            return false;
        }

        return copy($old_path, $new_path);
    }
}

/**
 * @param $old_path
 * @param $name
 * @param $transliteration
 * @return bool
 */
function rename_file($old_path, $name, $transliteration)
{
    $name = fix_filename($name, $transliteration);
    if (file_exists($old_path)) {
        $info = pathinfo($old_path);
        $new_path = $info['dirname'] . '/' . $name . '.' . $info['extension'];
        if (file_exists($new_path)) {
            return false;
        }

        return rename($old_path, $new_path);
    }
}

/**
 * @param $old_path
 * @param $name
 * @param $transliteration
 * @return bool
 */
function rename_folder($old_path, $name, $transliteration)
{
    $name = fix_filename($name, $transliteration);
    if (file_exists($old_path)) {
        $new_path = fix_dirname($old_path) . '/' . $name;
        if (file_exists($new_path)) {
            return false;
        }

        return rename($old_path, $new_path);
    }
}

/**
 * @param        $imgfile
 * @param        $imgthumb
 * @param        $newwidth
 * @param string $newheight
 * @return bool
 */
function create_img_gd($imgfile, $imgthumb, $newwidth, $newheight = '')
{
    if (image_check_memory_usage($imgfile, $newwidth, $newheight)) {
        require_once('php_image_magician.php');

        try {
            $magicianObj = new imageLib($imgfile);
        } catch (Exception $e) {
        }

        try {
            $magicianObj->resizeImage($newwidth, $newheight, 'crop');
        } catch (Exception $e) {
        }

        try {
            $magicianObj->saveImage($imgthumb, 80);
        } catch (Exception $e) {
        }

        return true;
    }

    return false;
}

/**
 * @param        $imgfile
 * @param        $imgthumb
 * @param        $newwidth
 * @param string $newheight
 * @return bool
 */
function create_img($imgfile, $imgthumb, $newwidth, $newheight = '')
{
    if (image_check_memory_usage($imgfile, $newwidth, $newheight)) {
        require_once('php_image_magician.php');

        try {
            $magicianObj = new imageLib($imgfile);
        } catch (Exception $e) {
        }

        try {
            $magicianObj->resizeImage($newwidth, $newheight, 'auto');
        } catch (Exception $e) {
        }

        try {
            $magicianObj->saveImage($imgthumb, 80);
        } catch (Exception $e) {
        }

        return true;
    } else {
        return false;
    }
}

/**
 * @param $size
 * @return string
 */
function makeSize($size)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $u = 0;
    while ((round($size / 1024) > 0) && ($u < 4)) {
        $size = $size / 1024;
        ++$u;
    }

    return (number_format($size, 0) . ' ' . $units[$u]);
}

/**
 * @param $path
 * @return false|int|mixed
 */
function foldersize($path)
{
    $total_size = 0;
    $files = scandir($path);
    $cleanPath = rtrim($path, '/') . '/';

    foreach ($files as $t) {
        if ('.' !== $t && '..' !== $t) {
            $currentFile = $cleanPath . $t;
            if (is_dir($currentFile)) {
                $size = foldersize($currentFile);
                $total_size += $size;
            } else {
                $size = filesize($currentFile);
                $total_size += $size;
            }
        }
    }

    return $total_size;
}

/**
 * @param bool $path
 * @param bool $path_thumbs
 */
function create_folder($path = false, $path_thumbs = false)
{
    $oldumask = umask(0);
    if ($path && !file_exists($path)) {
        mkdir($path, 0777, true);
    } // or even 01777 so you get the sticky bit set
    if ($path_thumbs && !file_exists($path_thumbs)) {
        mkdir($path_thumbs, 0777, true) or die("$path_thumbs cannot be found");
    } // or even 01777 so you get the sticky bit set
    umask($oldumask);
}

/**
 * @param $path
 * @param $ext
 */
function check_files_extensions_on_path($path, $ext)
{
    if (!is_dir($path)) {
        $fileinfo = pathinfo($path);
        if (!in_array(mb_strtolower($fileinfo['extension']), $ext)) {
            unlink($path);
        }
    } else {
        $files = scandir($path);
        foreach ($files as $file) {
            check_files_extensions_on_path(trim($path, '/') . '/' . $file, $ext);
        }
    }
}

/**
 * @param $phar
 * @param $files
 * @param $basepath
 * @param $ext
 */
function check_files_extensions_on_phar($phar, &$files, $basepath, $ext)
{
    foreach ($phar as $file) {
        if ($file->isFile()) {
            if (in_array(mb_strtolower($file->getExtension()), $ext)) {
                $files[] = $basepath . $file->getFileName();
            }
        } elseif ($file->isDir()) {
            $iterator = new DirectoryIterator($file);
            check_files_extensions_on_phar($iterator, $files, $basepath . $file->getFileName() . '/', $ext);
        }
    }
}

/**
 * @param $str
 * @param $transliteration
 * @return string
 */
function fix_filename($str, $transliteration)
{
    if ($transliteration) {
        if (function_exists('transliterator_transliterate')) {
            $str = transliterator_transliterate('Accents-Any', $str);
        } else {
            $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        }

        $str = preg_replace("/[^a-zA-Z0-9\.\[\]_| -]/", '', $str);
    }
    // Empty or incorrectly transliterated filename.
    // Here is a point: a good file UNKNOWN_LANGUAGE.jpg could become .jpg in previous code.
    // So we add that default 'file' name to fix that issue.
    if (0 === mb_strpos($str, '.')) {
        $str = 'file' . $str;
    }

    return trim($str);
}

/**
 * @param $str
 * @return string|string[]
 */
function fix_dirname($str)
{
    return str_replace('~', ' ', dirname(str_replace(' ', '~', $str)));
}

/**
 * @param $str
 * @return bool|false|string|string[]|null
 */
function fix_strtoupper($str)
{
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($str);
    }

    return mb_strtoupper($str);
}

/**
 * @param $str
 * @return bool|false|string|string[]|null
 */
function fix_strtolower($str)
{
    if (function_exists('mb_strtoupper')) {
        return mb_strtolower($str);
    }

    return mb_strtolower($str);
}

/**
 * @param $path
 * @param $transliteration
 * @return string
 */
function fix_path($path, $transliteration)
{
    $info = pathinfo($path);
    $tmp_path = $info['dirname'];
    $str = fix_filename($info['filename'], $transliteration);
    if ('' != $tmp_path) {
        return $tmp_path . DIRECTORY_SEPARATOR . $str;
    }

    return $str;
}

/**
 * @return string
 */
function base_url()
{
    return sprintf(
        '%s://%s',
        isset($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'] ? 'https' : 'http',
        $_SERVER['HTTP_HOST']
    );
}

/**
 * @param $current_path
 * @param $fld
 * @return bool
 */
function config_loading($current_path, $fld)
{
    if (file_exists($current_path . $fld . '.config')) {
        require_once($current_path . $fld . '.config');

        return true;
    }
    echo '!!!!' . $parent = fix_dirname($fld);
    if ('.' !== $parent && !empty($parent)) {
        config_loading($current_path, $parent);
    }

    return false;
}

/**
 * @param $img
 * @param $max_breedte
 * @param $max_hoogte
 * @return bool
 */
function image_check_memory_usage($img, $max_breedte, $max_hoogte)
{
    if (file_exists($img)) {
        $K64 = 65536;    // number of bytes in 64K
        $memory_usage = memory_get_usage();
        $memory_limit = abs((int)(str_replace('M', '', ini_get('memory_limit')) * 1024 * 1024));
        $image_properties = getimagesize($img);
        $image_width = $image_properties[0];
        $image_height = $image_properties[1];
        $image_bits = $image_properties['bits'];
        $image_memory_usage = $K64 + ($image_width * $image_height * ($image_bits) * 2);
        $thumb_memory_usage = $K64 + ($max_breedte * $max_hoogte * ($image_bits) * 2);
        $memory_needed = (int)($memory_usage + $image_memory_usage + $thumb_memory_usage);

        if ($memory_needed > $memory_limit) {
            ini_set('memory_limit', ((int)($memory_needed / 1024 / 1024) + 5) . 'M');
            if (ini_get('memory_limit') == ((int)($memory_needed / 1024 / 1024) + 5) . 'M') {
                return true;
            }

            return false;
        }

        return true;
    }

    return false;
}

/**
 * @param $haystack
 * @param $needle
 * @return bool
 */
function endsWith($haystack, $needle)
{
    return '' === $needle || mb_substr($haystack, -mb_strlen($needle)) === $needle;
}

/**
 * @param $targetPath
 * @param $targetFile
 * @param $name
 * @param $current_path
 * @param $relative_image_creation
 * @param $relative_path_from_current_pos
 * @param $relative_image_creation_name_to_prepend
 * @param $relative_image_creation_name_to_append
 * @param $relative_image_creation_width
 * @param $relative_image_creation_height
 * @param $fixed_image_creation
 * @param $fixed_path_from_filemanager
 * @param $fixed_image_creation_name_to_prepend
 * @param $fixed_image_creation_to_append
 * @param $fixed_image_creation_width
 * @param $fixed_image_creation_height
 * @return bool
 */
function new_thumbnails_creation(
    $targetPath,
    $targetFile,
    $name,
    $current_path,
    $relative_image_creation,
    $relative_path_from_current_pos,
    $relative_image_creation_name_to_prepend,
    $relative_image_creation_name_to_append,
    $relative_image_creation_width,
    $relative_image_creation_height,
    $fixed_image_creation,
    $fixed_path_from_filemanager,
    $fixed_image_creation_name_to_prepend,
    $fixed_image_creation_to_append,
    $fixed_image_creation_width,
    $fixed_image_creation_height
) {
    //create relative thumbs
    $all_ok = true;
    if ($relative_image_creation) {
        foreach ($relative_path_from_current_pos as $k => $path) {
            if ('' != $path && '/' !== $path[mb_strlen($path) - 1]) {
                $path .= '/';
            }
            if (!file_exists($targetPath . $path)) {
                create_folder($targetPath . $path, false);
            }
            $info = pathinfo($name);
            if (!endsWith($targetPath, $path)) {
                if (!create_img($targetFile, $targetPath . $path . $relative_image_creation_name_to_prepend[$k] . $info['filename'] . $relative_image_creation_name_to_append[$k] . '.' . $info['extension'], $relative_image_creation_width[$k], $relative_image_creation_height[$k])) {
                    $all_ok = false;
                }
            }
        }
    }

    //create fixed thumbs
    if ($fixed_image_creation) {
        foreach ($fixed_path_from_filemanager as $k => $path) {
            if ('' != $path && '/' !== $path[mb_strlen($path) - 1]) {
                $path .= '/';
            }
            $base_dir = $path . substr_replace($targetPath, '', 0, mb_strlen($current_path));
            if (!file_exists($base_dir)) {
                create_folder($base_dir, false);
            }
            $info = pathinfo($name);
            if (!create_img($targetFile, $base_dir . $fixed_image_creation_name_to_prepend[$k] . $info['filename'] . $fixed_image_creation_to_append[$k] . '.' . $info['extension'], $fixed_image_creation_width[$k], $fixed_image_creation_height[$k])) {
                $all_ok = false;
            }
        }
    }

    return $all_ok;
}
