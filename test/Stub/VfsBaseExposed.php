<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Stub;

use Horde_Vfs_Null;

/**
 * Exposes protected methods from Horde_Vfs_Base for unit testing.
 */
class VfsBaseExposed extends Horde_Vfs_Null
{
    public function filterMatch(string|array|null $filter, string $filename): bool
    {
        return (bool) $this->_filterMatch($filter, $filename);
    }

    public function checkDestination(string $path, string $dest): void
    {
        $this->_checkDestination($path, $dest);
    }

    public function setFeatures(array $features): void
    {
        $this->_features = $features;
    }

    public function setCredentials(array $credentials): void
    {
        $this->_credentials = $credentials;
    }

    public function setPermissions(array $permissions): void
    {
        $this->_permissions = $permissions;
    }
}
