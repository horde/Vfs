<?php
/**
 * Prepare the test setup.
 */
namespace Horde\Vfs\Test\SqlFile;
use Horde_Db_Adapter_Mysql;

/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class MysqlTest extends BaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('mysql')) {
            self::$reason = 'No mysql extension';
            return;
        }
        $config = self::getConfig('VFS_SQLFILE_MYSQL_TEST_CONFIG',
                                  __DIR__ . '/..');
        if ($config && !empty($config['vfs']['sqlfile']['mysql'])) {
            self::$db = new Horde_Db_Adapter_Mysql($config['vfs']['sqlfile']['mysql']);
            parent::setUpBeforeClass();
        }
    }
}
