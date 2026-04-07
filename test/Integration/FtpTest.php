<?php

/**
 * Test the FTP based virtual file system.
 *
 * Copyright 2012-2026 Horde LLC (http://www.horde.org/)
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
class FtpTest extends TestBase
{
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

    public function testChmod(): void
    {
        $this->_chmod();
    }

    public function testNullRoot(): void
    {
        $this->_nullRoot();
    }

    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('ftp')) {
            self::$reason = 'No ftp extension';
            return;
        }
        $config = ConfigHelper::getConfig('VFS_FTP_TEST_CONFIG', __DIR__ . '/..');
        if ($config && !empty($config['vfs']['ftp'])) {
            self::$vfs = Horde_Vfs::factory('Ftp', $config['vfs']['ftp']);
        } else {
            self::$reason = 'No ftp configuration';
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
}
