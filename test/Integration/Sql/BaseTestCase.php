<?php

/**
 * Copyright 2012-2026 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Vfs\Test\Integration\Sql;

use Horde\Vfs\Test\Integration\TestBase;
use Horde_Db_Migration_Migrator;
use Horde_Log_Handler_Cli;
use Horde_Log_Logger;
use Horde_Vfs_Sql;
use PEAR_Config;
use PHPUnit\Framework\Attributes\Depends;

class BaseTestCase extends TestBase
{
    protected static $db;

    protected static $migrator;

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
        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Cli());
        //self::$db->setLogger($logger);
        $dir = __DIR__ . '/../../../migration/Horde/Vfs';
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
                  'schemaTableName' => 'horde_vfs_schema_info')
        );
        self::$migrator->up();

        self::$vfs = new Horde_Vfs_Sql(array('db' => self::$db));
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$migrator) {
            if (self::$db) {
                self::$db->delete('DELETE FROM horde_vfs');
                self::$db->delete('DELETE FROM horde_muvfs');
            }
            self::$migrator->down();
        }
        if (self::$db) {
            self::$db->disconnect();
        }
        self::$db = self::$migrator = null;
        parent::tearDownAfterClass();
    }
}
