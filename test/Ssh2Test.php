<?php
/**
 * Test the SSH based virtual file system.
 *
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
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
namespace Horde\Vfs\Test;

class Ssh2Test extends TestBase
{
    public function testListEmpty()
    {
        $this->_listEmpty();
    }

    public function testCreateFolder()
    {
        $this->_createFolderStructure();
    }

    /**
     * @depends testCreateFolder
     */
    public function testWriteData()
    {
        $this->_writeData();
    }

    /**
     * @depends testCreateFolder
     */
    public function testWrite()
    {
        $this->_write();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testRead()
    {
        $this->_read();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testReadFile()
    {
        $this->_readFile();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testReadStream()
    {
        $this->_readStream();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testSize()
    {
        $this->_size();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testFolderSize()
    {
        $this->_folderSize();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testVfsSize()
    {
        $this->_vfsSize();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testCopy()
    {
        $this->_copy();
    }

    /**
     * @depends testCopy
     */
    public function testRename()
    {
        $this->_rename();
    }

    /**
     * @depends testRename
     */
    public function testMove()
    {
        $this->_move();
    }

    /**
     * @depends testMove
     */
    public function testDeleteFile()
    {
        $this->_deleteFile();
    }

    /**
     * @depends testMove
     */
    public function testDeleteFolder()
    {
        $this->_deleteFolder();
    }

    /**
     * @depends testMove
     */
    public function testEmptyFolder()
    {
        $this->_emptyFolder();
    }

    /**
     * @depends testMove
     */
    public function testQuota()
    {
        $this->_quota();
    }

    /**
     * @depends testQuota
     */
    public function testListFolder()
    {
        $this->_listFolder();
    }

    public function testChmod()
    {
        $this->_chmod();
    }

    public function testNullRoot()
    {
        $this->_nullRoot();
    }

    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('ssh2')) {
            self::$reason = 'No ssh2 extension';
            return;
        }
        $config = self::getConfig('VFS_SSH2_TEST_CONFIG', __DIR__);
        if ($config && !empty($config['vfs']['ssh2'])) {
            self::$vfs = Horde_Vfs::factory('Ssh2', $config['vfs']['ssh2']);
        } else {
            self::$reason = 'No ssh2 configuration';
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
