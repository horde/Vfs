<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Unit;

use Horde_Vfs_Exception;
use Horde_Vfs_Null;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Vfs_Null::class)]
class NullDriverTest extends TestCase
{
    private Horde_Vfs_Null $vfs;

    protected function setUp(): void
    {
        $this->vfs = new Horde_Vfs_Null();
    }

    public function testReadReturnsEmptyString(): void
    {
        $this->assertSame('', $this->vfs->read('/path', 'file'));
    }

    public function testSizeReturnsZero(): void
    {
        $this->assertSame(0, $this->vfs->size('/path', 'file'));
    }

    public function testWriteDataDoesNotThrow(): void
    {
        $this->vfs->writeData('/path', 'file', 'data');
        $this->assertTrue(true);
    }

    public function testWriteDoesNotThrow(): void
    {
        $this->vfs->write('/path', 'file', '/tmp/file');
        $this->assertTrue(true);
    }

    public function testDeleteFileDoesNotThrow(): void
    {
        $this->vfs->deleteFile('/path', 'file');
        $this->assertTrue(true);
    }

    public function testCreateFolderDoesNotThrow(): void
    {
        $this->vfs->createFolder('/path', 'dir');
        $this->assertTrue(true);
    }

    public function testDeleteFolderDoesNotThrow(): void
    {
        $this->vfs->deleteFolder('/path', 'dir');
        $this->assertTrue(true);
    }

    public function testRenameDoesNotThrow(): void
    {
        $this->vfs->rename('/old', 'file', '/new', 'file');
        $this->assertTrue(true);
    }

    public function testChangePermissionsDoesNotThrow(): void
    {
        $this->vfs->changePermissions('/path', 'file', '644');
        $this->assertTrue(true);
    }

    public function testListFolderReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->vfs->listFolder('/path'));
    }

    public function testReadFileThrows(): void
    {
        $this->expectException(Horde_Vfs_Exception::class);
        $this->expectExceptionMessage('Unable to create temporary file.');
        $this->vfs->readFile('/path', 'file');
    }

    public function testReadByteRangeReturnsEmptyWithZeroRemaining(): void
    {
        $offset = 0;
        $remaining = -1;
        $result = $this->vfs->readByteRange('/path', 'file', $offset, 10, $remaining);
        $this->assertSame('', $result);
        $this->assertSame(0, $remaining);
    }
}
