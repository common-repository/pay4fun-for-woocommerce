<?php

// Make sure we don't expose any info if called directly
defined('ABSPATH') || exit;

class P4FWC_LogViewer
{
    private const api_error_log = "/helpers/debug.log";
    private const plugin_error_log = "/includes/debug.log";
    private const plugin_path = P4F_PLUGIN_DIR;

    public static function getAPILog()
    {
        if (is_admin()) {
            return self::readFile(self::plugin_path . self::api_error_log);
        }
        return '';
    }

    public static function getPluginLog()
    {
        if (is_admin()) {
            return self::readFile(self::plugin_path . self::plugin_error_log);
        }
        return '';
    }

    private static function readFile(String $filename)
    {
        if (file_exists($filename)) {
            return file_get_contents($filename);
        }
        return '';
    }


    public static function clearLogs()
    {
        return
            file_exists(self::plugin_path . self::api_error_log) &&
            file_exists(self::plugin_path . self::plugin_error_log) &&
            unlink(self::plugin_path . self::api_error_log) &&
            unlink(self::plugin_path . self::plugin_error_log);
    }
}
