<?php
/**
 * Prepare the test setup.
 */
namespace Horde\Vfs\Test\SqlFile;
use Horde_Db_Adapter_Mysqli;
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class MysqliTest extends BaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }
        $config = self::getConfig('VFS_SQLFILE_MYSQLI_TEST_CONFIG',
                                  __DIR__ . '/..');
        if ($config && !empty($config['vfs']['sqlfile']['mysqli'])) {
            self::$db = new Horde_Db_Adapter_Mysqli($config['vfs']['sqlfile']['mysqli']);
            parent::setUpBeforeClass();
        }
    }
}
