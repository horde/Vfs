<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Unit;

use Horde_Vfs;
use Horde_Vfs_Base;
use Horde_Vfs_Exception;
use Horde_Vfs_File;
use Horde_Vfs_Null;
use Horde_Vfs_Smb;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Vfs::class)]
class FactoryTest extends TestCase
{
    public function testFactoryReturnsNullDriver(): void
    {
        $vfs = Horde_Vfs::factory('Null');
        $this->assertInstanceOf(Horde_Vfs_Null::class, $vfs);
    }

    public function testFactoryReturnsFileDriver(): void
    {
        $vfs = Horde_Vfs::factory('File', ['vfsroot' => sys_get_temp_dir()]);
        $this->assertInstanceOf(Horde_Vfs_File::class, $vfs);
    }

    public function testFactoryReturnsSmbDriver(): void
    {
        $vfs = Horde_Vfs::factory('Smb');
        $this->assertInstanceOf(Horde_Vfs_Smb::class, $vfs);
    }

    public function testFactoryThrowsOnUnknownDriver(): void
    {
        $this->expectException(Horde_Vfs_Exception::class);
        Horde_Vfs::factory('NonExistentDriver');
    }

    public function testFactoryPassesParams(): void
    {
        $vfs = Horde_Vfs::factory('Null', ['custom_key' => 'custom_value']);
        $this->assertInstanceOf(Horde_Vfs_Base::class, $vfs);
        $this->assertSame('custom_value', $vfs->getParam('custom_key'));
    }

    public function testFactoryIsCaseInsensitive(): void
    {
        $vfs = Horde_Vfs::factory('null');
        $this->assertInstanceOf(Horde_Vfs_Null::class, $vfs);
    }
}
