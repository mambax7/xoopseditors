<?php

/** This file is part of KCFinder project
 *
 * @desc      Helper class for downloading URLs
 * @package   KCFinder
 * @version   3.12
 * @author    Pavel Tzonkov <sunhater@sunhater.com>
 * @copyright 2010-2014 KCFinder Project
 * @license   http://opensource.org/licenses/GPL-3.0 GPLv3
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPLv3
 * @link      http://kcfinder.sunhater.com
 */

namespace kcfinder;

class phpGet
{

    public static $methods    = ['curl', 'fopen', 'http', 'socket'];
    public static $urlExpr    = '/^([a-z]+):\/\/((([\p{L}\d\-]+\.)+[\p{L}]{1,4})(\:(\d{1,6}))?(\/.*)*)?$/u';
    public static $socketExpr = '/^[A-Z]+\/\d+(\.\d+)\s+\d+\s+OK\s*([a-zA-Z0-9\-]+\:\s*[^\n]*\n)*\s*([a-f0-9]+\r?\n)?(.*)$/s';

    public static function get($url, $file = null, $method = null)
    {
        if (true === $file) {
            $file = basename($url);
        }
        if (null !== $file) {
            if (is_dir($file)) {
                $file = rtrim($file, '/') . '/' . basename($url);
            }
            $exists = file_exists($file);
            if (!@touch($file)) {
                return false;
            }
            if (!$exists) {
                @unlink($file);
            }
        }

        if (in_array($method, self::$methods, true)) {
            $check = "check_$method";
            $get   = "get_$method";
            if (self::$check()) {
                $content = self::$get($url);
            } else {
                return false;
            }
        } else {
            foreach (self::$methods as $m) {
                $check = "check_$m";
                $get   = "get_$m";
                if (self::$check()) {
                    $content = self::$get($url);
                    if (((true !== $method) && ('all' != strtolower($method)))
                        || (false !== $content)) {
                        break;
                    }
                }
            }
            if (!isset($content)) {
                return false;
            }
        }

        return (null !== $file) ? @file_put_contents($file, $content) : $content;
    }

    public static function get_fopen($url)
    {
        return @file_get_contents($url);
    }

    public static function get_curl($url)
    {
        return ((false !== ($curl = @curl_init($url)))
                && (@ob_start() || (@curl_close($curl) && false))
                && (@curl_exec($curl) || (@curl_close($curl) && false))
                && ((false !== ($content = @ob_get_clean())) || (@curl_close($curl) && false))
                && (@curl_close($curl) || true)) ? $content : false;
    }

    public static function get_http($url)
    {
        return ((false !== ($content = @http_get($url)))
                && ((preg_match(self::$socketExpr, $content, $match)
                     && false !== ($content = $match[4]))
                    || true)) ? $content : false;
    }

    public static function get_socket($url)
    {
        if (!preg_match(self::$urlExpr, $url, $match)) {
            return false;
        }

        $protocol = $match[1];
        $host     = $match[3];
        $port     = strlen($match[6]) ? $match[6] : 80;
        $path     = strlen($match[7]) ? $match[7] : '/';

        $cmd = "GET $path " . strtoupper($protocol) . "/1.1\r\n" . "Host: $host\r\n" . "Connection: Close\r\n\r\n";

        if ((false !== ($socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))
            && (false !== @socket_connect($socket, $host, $port))
            && (false !== @socket_write($socket, $cmd, strlen($cmd)))
            && (false !== ($content = @socket_read($socket, 2048)))) {
            do {
                $piece   = @socket_read($socket, 2048);
                $content .= $piece;
            } while ($piece);

            $content = preg_match(self::$socketExpr, $content, $match) ? $match[4] : false;
        }

        if (isset($socket) && is_resource($socket)) {
            @socket_close($socket);
        } else {
            return false;
        }

        return isset($content) ? $content : false;
    }

    private static function check_fopen()
    {
        return ini_get('allow_url_fopen')
               && function_exists('file_get_contents');
    }

    private static function check_curl()
    {
        return function_exists('curl_init')
               && function_exists('curl_exec')
               && function_exists('curl_close')
               && function_exists('ob_start')
               && function_exists('ob_get_clean');
    }

    private static function check_http()
    {
        return function_exists('http_get');
    }

    private static function check_socket()
    {
        return function_exists('socket_create')
               && function_exists('socket_connect')
               && function_exists('socket_write')
               && function_exists('socket_read')
               && function_exists('socket_close');
    }
}
