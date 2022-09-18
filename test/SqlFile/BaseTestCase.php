<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
namespace Horde\Vfs\Test\SqlFile;
use Horde\Vfs\Test\TestBase;
use Horde_Log_Logger;
use PEAR_Config;
use Horde_Db_Migration_Migrator;
use Horde_Log_Handler_Cli;
use Horde_Vfs_SqlFile;

class BaseTestCase extends TestBase
{
    protected static $db;

    protected static $migrator;

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
    public function testReadByteRange()
    {
        $this->_readByteRange();
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

    public function testNullRoot()
    {
        $this->_nullRoot();
    }

    public static function setUpBeforeClass(): void
    {
        // The SqlFile VFS driver needs to be refactored to a real composite
        // driver.
        return;

        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Cli());
        //self::$db->setLogger($logger);
        $dir = __DIR__ . '/../../../../migration/Horde/Vfs';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Vfs/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            array('migrationsPath' => $dir,
                  'schemaTableName' => 'horde_vfs_schema_info'));
        self::$migrator->up();

        self::$vfs = new Horde_Vfs_SqlFile(array('db' => self::$db));
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$migrator) {
            self::$migrator->down();
        }
        //self::$db->disconnect();
        self::$db = null;
        parent::tearDownAfterClass();
    }

    public function setUp(): void
    {
        $this->markTestIncomplete('The SqlFile VFS driver needs to be refactored to a real composite driver.');
        parent::setUp();
    }
}
