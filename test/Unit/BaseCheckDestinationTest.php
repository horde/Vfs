<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Unit;

use Horde\Vfs\Test\Stub\VfsBaseExposed;
use Horde_Vfs_Base;
use Horde_Vfs_Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Vfs_Base::class)]
class BaseCheckDestinationTest extends TestCase
{
    private VfsBaseExposed $vfs;

    protected function setUp(): void
    {
        $this->vfs = new VfsBaseExposed();
    }

    public function testSamePathThrows(): void
    {
        $this->expectException(Horde_Vfs_Exception::class);
        $this->vfs->checkDestination('foo', 'foo');
    }

    public function testSamePathWithTrailingSlashThrows(): void
    {
        $this->expectException(Horde_Vfs_Exception::class);
        $this->vfs->checkDestination('foo/', 'foo');
    }

    public function testDestWithTrailingSlashMatchesSource(): void
    {
        $this->expectException(Horde_Vfs_Exception::class);
        $this->vfs->checkDestination('foo', 'foo/');
    }

    public function testDifferentPathsDoNotThrow(): void
    {
        $this->vfs->checkDestination('foo', 'bar');
        $this->assertTrue(true);
    }

    public function testSubdirDoesNotThrow(): void
    {
        $this->vfs->checkDestination('foo', 'foo/bar');
        $this->assertTrue(true);
    }

    public function testEmptyPathsThrow(): void
    {
        $this->expectException(Horde_Vfs_Exception::class);
        $this->vfs->checkDestination('', '');
    }
}
