<?php

namespace App\Utils;

/**
 * Filesystem utilities
 */
class Filesystem
{
    /**
     * Create a directory at the path $dir if it doesn't exist
     *
     * @param string $dir
     * @param int    $permissions
     * @return void
     */
    public static function ensureDirectoryExists(string $dir, int $permissions = 0777): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, $permissions);
        }
    }

    /**
     * Recursively remove $target
     *
     * @param string $target
     * @return bool true on success or false on failure
     */
    public static function rrmdir(string $target): bool
    {
        if (!file_exists($target)) {
            return false;
        }

        if (is_file($target) || is_link($target)) {
            return unlink($target);
        }

        $dir = dir($target);
        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            self::rrmdir($target . DIRECTORY_SEPARATOR . $entry);
        }

        $dir->close();
        return rmdir($target);
    }
}
