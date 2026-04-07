<?php

/**
 * Test the MongoDB virtual file system.
 *
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Vfs\Test\Integration;

use Horde_Vfs;
use PHPUnit\Framework\Attributes\Depends;

class MongoTest extends TestBase
{
    protected static $_mongo;

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

    public function testNullRoot(): void
    {
        $this->_nullRoot();
    }

    public static function setUpBeforeClass(): void
    {
        if (!class_exists('Horde_Test_Factory_Mongo')) {
            self::$reason = 'Horde_Test_Factory_Mongo not available.';
            return;
        }
        if (($config = ConfigHelper::getConfig('VFS_MONGO_TEST_CONFIG', __DIR__ . '/..')) &&
            isset($config['vfs']['mongo']['hostspec'])) {
            $factory = new \Horde_Test_Factory_Mongo();
            self::$_mongo = $factory->create(array(
                'config' => $config['vfs']['mongo']['hostspec'],
                'dbname' => 'horde_vfs_mongodbtest'
            ));
        }

        if (empty(self::$_mongo)) {
            self::$reason = 'MongoDB not available.';
        } else {
            self::$vfs = Horde_Vfs::factory('Mongo', array(
                'mongo_db' => self::$_mongo
            ));
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!empty(self::$_mongo)) {
            self::$_mongo->selectDB(null)->drop();
        }

        parent::tearDownAfterClass();
    }
}
