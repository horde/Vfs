<?php

/**
 * Test the file based virtual file system.
 *
 * Copyright 2008-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Michael Slusarz <slusarz@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Vfs\Test\Integration;

use Horde_Vfs;
use Horde_Vfs_Exception;
use PHPUnit\Framework\Attributes\Depends;

/**
 * @coversNothing
 */
class FileTest extends TestBase
{
    public function testListEmpty(): void
    {
        $this->_listEmpty();
    }

    public function testCreateFolder(): void
    {
        $this->_createFolderStructure();
        $this->markTestIncomplete();
    }

    #[Depends('testCreateFolder')]
    public function testWriteData(): void
    {
        $this->_writeData();
    }

    #[Depends('testCreateFolder')]
    public function testWrite(): void
    {
        $this->_write();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testRead(): void
    {
        $this->_read();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testReadFile(): void
    {
        $this->_readFile();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testReadStream(): void
    {
        $this->_readStream();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testReadByteRange(): void
    {
        $this->_readByteRange();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testSize(): void
    {
        $this->_size();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testFolderSize(): void
    {
        $this->_folderSize();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testVfsSize(): void
    {
        $this->_vfsSize();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testCopy(): void
    {
        $this->_copy();
    }

    #[Depends('testCopy')]
    public function testRename(): void
    {
        $this->_rename();
    }

    #[Depends('testRename')]
    public function testMove(): void
    {
        $this->_move();
    }

    #[Depends('testMove')]
    public function testDeleteFile(): void
    {
        $this->_deleteFile();
    }

    #[Depends('testMove')]
    public function testDeleteFolder(): void
    {
        $this->_deleteFolder();
    }

    #[Depends('testMove')]
    public function testEmptyFolder(): void
    {
        $this->_emptyFolder();
    }

    #[Depends('testMove')]
    public function testQuota(): void
    {
        $this->_quota();
    }

    #[Depends('testQuota')]
    public function testListFolder(): void
    {
        $this->_listFolder();
    }

    public function testListFolderWithoutPermissions(): void
    {
        $this->expectException(Horde_Vfs_Exception::class);
        $this->expectExceptionMessage('Unable to access VFS directory root.');

        if (!is_dir('/root')) {
            $this->markTestSkipped('No /root folder to test permissions.');
        }
        $vfs = Horde_Vfs::factory('File', ['vfsroot' => '/']);
        $vfs->listFolder('root');
    }

    public function testChmod(): void
    {
        $this->_chmod();
    }

    public function testNullRoot(): void
    {
        $this->_nullRoot();
    }

    public function testDeleteUnusalFileNames(): void
    {
        $file = '高&执&行&力&的&打&造.txt';
        $dir = '.horde/foo';
        $path = sys_get_temp_dir() . '/vfsfiletest/' . $dir . '/' . $file;
        self::$vfs->writeData($dir, $file, 'some content', true);
        self::$vfs->delete($dir, $file);
        $this->assertFileDoesNotExist($path);
    }

    public static function setUpBeforeClass(): void
    {
        self::$vfs = Horde_Vfs::factory('File', [
            'vfsroot' => sys_get_temp_dir() . '/vfsfiletest',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        system('rm -r ' . sys_get_temp_dir() . '/vfsfiletest');
        parent::tearDownAfterClass();
    }
}
