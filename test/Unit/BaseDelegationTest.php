<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Unit;

use Horde_Vfs_Base;
use Horde_Vfs_Exception;
use Horde_Vfs_Null;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Vfs_Base::class)]
class BaseDelegationTest extends TestCase
{
    public function testExistsReturnsTrueWhenInList(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, ['listFolder']);
        $vfs->expects($this->once())
            ->method('listFolder')
            ->with('')
            ->willReturn(['file1' => ['name' => 'file1', 'type' => 'txt']]);

        $this->assertTrue($vfs->exists('', 'file1'));
    }

    public function testExistsReturnsFalseWhenNotInList(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, ['listFolder']);
        $vfs->expects($this->once())
            ->method('listFolder')
            ->with('')
            ->willReturn([]);

        $this->assertFalse($vfs->exists('', 'file1'));
    }

    public function testExistsReturnsFalseOnException(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, ['listFolder']);
        $vfs->expects($this->once())
            ->method('listFolder')
            ->with('')
            ->willThrowException(new Horde_Vfs_Exception('fail'));

        $this->assertFalse($vfs->exists('', 'file1'));
    }

    public function testIsFolderReturnsTrueWhenFound(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, ['listFolder']);
        $vfs->expects($this->once())
            ->method('listFolder')
            ->with('', null, true, true)
            ->willReturn(['mydir' => ['name' => 'mydir', 'type' => '**dir']]);

        $this->assertTrue($vfs->isFolder('', 'mydir'));
    }

    public function testIsFolderReturnsFalseWhenNotFound(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, ['listFolder']);
        $vfs->expects($this->once())
            ->method('listFolder')
            ->with('', null, true, true)
            ->willReturn([]);

        $this->assertFalse($vfs->isFolder('', 'mydir'));
    }

    public function testIsFolderReturnsFalseOnException(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, ['listFolder']);
        $vfs->expects($this->once())
            ->method('listFolder')
            ->with('', null, true, true)
            ->willThrowException(new Horde_Vfs_Exception('fail'));

        $this->assertFalse($vfs->isFolder('', 'mydir'));
    }

    public function testEmptyFolderDeletesFoldersAndFiles(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, [
            'listFolder',
            'deleteFolder',
            'deleteFile',
        ]);

        $vfs->expects($this->exactly(2))
            ->method('listFolder')
            ->willReturnCallback(function (string $path, $filter, $dotfiles, $dironly) {
                if ($dironly) {
                    return ['subdir' => ['name' => 'subdir', 'type' => '**dir']];
                }
                return ['file1' => ['name' => 'file1', 'type' => 'txt']];
            });

        $vfs->expects($this->once())
            ->method('deleteFolder')
            ->with('test', 'subdir', true);

        $vfs->expects($this->once())
            ->method('deleteFile')
            ->with('test', 'file1');

        $vfs->emptyFolder('test');
    }

    public function testMoveForFileCallsCopyThenDeleteFile(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, [
            'copy',
            'isFolder',
            'deleteFile',
        ]);

        $vfs->expects($this->once())
            ->method('copy')
            ->with('src', 'file1', 'dest', false);

        $vfs->expects($this->once())
            ->method('isFolder')
            ->with('src', 'file1')
            ->willReturn(false);

        $vfs->expects($this->once())
            ->method('deleteFile')
            ->with('src', 'file1');

        $vfs->move('src', 'file1', 'dest');
    }

    public function testMoveForFolderCallsCopyThenDeleteFolder(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, [
            'copy',
            'isFolder',
            'deleteFolder',
        ]);

        $vfs->expects($this->once())
            ->method('copy')
            ->with('src', 'dir1', 'dest', false);

        $vfs->expects($this->once())
            ->method('isFolder')
            ->with('src', 'dir1')
            ->willReturn(true);

        $vfs->expects($this->once())
            ->method('deleteFolder')
            ->with('src', 'dir1', true);

        $vfs->move('src', 'dir1', 'dest');
    }

    public function testGetCurrentDirectoryReturnsEmptyString(): void
    {
        $vfs = new Horde_Vfs_Null();
        $this->assertSame('', $vfs->getCurrentDirectory());
    }

    public function testDeleteAliasesDeleteFile(): void
    {
        $vfs = $this->createPartialMock(Horde_Vfs_Base::class, ['deleteFile']);

        $vfs->expects($this->once())
            ->method('deleteFile')
            ->with('path', 'name');

        $vfs->delete('path', 'name');
    }
}
