<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Integration;

/**
 * Loads test configuration from environment variable or conf.php file.
 *
 * Replaces Horde\Test\TestCase::getConfig() so integration tests do not
 * require the horde/test package.
 */
class ConfigHelper
{
    /**
     * Load test configuration.
     *
     * Checks for a JSON-encoded environment variable first, then falls
     * back to a conf.php file at the given path.
     *
     * @param string $env     Environment variable name.
     * @param string $path    Directory to look for conf.php in.
     * @param array  $default Default configuration to merge with.
     *
     * @return array|null  Configuration array, or null if not available.
     */
    public static function getConfig(
        string $env,
        string $path,
        array $default = [],
    ): ?array {
        $conf = [];
        $envValue = getenv($env);

        if ($envValue !== false) {
            $json = json_decode($envValue, true);
            if (is_array($json)) {
                return array_replace_recursive($default, $json);
            }
        }

        $configFile = $path . '/conf.php';
        if (file_exists($configFile)) {
            require $configFile;
            return $conf ?: null;
        }

        return null;
    }
}
