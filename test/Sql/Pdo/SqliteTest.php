<?php
/**
 * Prepare the test setup.
 */
namespace Horde\Vfs\Test\Sql\Pdo;
use Horde\Vfs\Test\Sql\BaseTestCase;
use \Horde_Test_Factory_Db;
use Horde_Test_Exception;

/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class SqliteTest extends BaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        $factory_db = new Horde_Test_Factory_Db();

        if (class_exists('Horde_Db_Adapter_Pdo_Sqlite')) {
            try {
                self::$db = $factory_db->create();
                parent::setUpBeforeClass();
            } catch (Horde_Test_Exception $e) {
                self::$reason = 'Sqlite not available';
            }
        }         
    }
}
