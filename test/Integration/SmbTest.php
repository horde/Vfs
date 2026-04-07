<?php

/**
 * Test the SMB based virtual file system.
 *
 * Copyright 2011-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
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
class SmbTest extends TestBase
{
    private string $_oldTimezone = '';

    public static function setUpBeforeClass(): void
    {
        $config = ConfigHelper::getConfig('VFS_SMB_TEST_CONFIG', __DIR__ . '/..');
        if ($config && !empty($config['vfs']['smb'])) {
            if (!is_executable($config['vfs']['smb']['smbclient'])) {
                self::$reason = 'No executable smbclient';
                return;
            }
            self::$vfs = Horde_Vfs::factory('Smb', $config['vfs']['smb']);
        } else {
            self::$reason = 'No smb configuration';
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$vfs) {
            try {
                self::$vfs->emptyFolder('');
            } catch (Horde_Vfs_Exception $e) {
                echo $e;
            }
        }
        parent::tearDownAfterClass();
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->_oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
    }

    public function tearDown(): void
    {
        if ($this->_oldTimezone !== '') {
            date_default_timezone_set($this->_oldTimezone);
        }
    }

    public function testListEmpty(): void
    {
        $this->_listEmpty();
    }

    public function testCreateFolder(): void
    {
        $this->_createFolderStructure();
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
    public function testSize(): void
    {
        $this->_size();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testFolderSize(): void
    {
        $this->markTestIncomplete();
        $this->_folderSize();
    }

    #[Depends('testWrite')]
    #[Depends('testWriteData')]
    public function testVfsSize(): void
    {
        $this->markTestIncomplete();
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
        $this->markTestIncomplete();
        $this->_quota();
    }

    #[Depends('testQuota')]
    public function testListFolder(): void
    {
        $this->_listFolder();
    }

    public function testNullRoot(): void
    {
        $this->_nullRoot();
    }

    public function testHostspecWithPath(): void
    {
        self::$vfs->createFolder('', 'hostspectest');
        self::$vfs->createFolder('hostspectest', 'directory');
        self::$vfs->createFolder('hostspectest/directory', 'subdir');
        $config = ConfigHelper::getConfig('VFS_SMB_TEST_CONFIG', __DIR__ . '/..');
        $config['vfs']['smb']['share'] .= '/hostspectest';
        $vfs = Horde_Vfs::factory('Smb', $config['vfs']['smb']);
        $this->assertEquals(
            ['subdir'],
            array_keys($vfs->listFolder('directory'))
        );
    }
}
