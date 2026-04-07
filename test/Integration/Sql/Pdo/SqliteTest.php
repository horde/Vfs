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

namespace Horde\Vfs\Test\Integration\Sql\Pdo;

use Horde\Vfs\Test\Integration\Sql\BaseTestCase;
use Horde_Db_Adapter_Pdo_Sqlite;
use PDO;
use Exception;

/**
 * @coversNothing
 */
class SqliteTest extends BaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('pdo')
            || !in_array('sqlite', PDO::getAvailableDrivers())) {
            self::$reason = 'No sqlite extension or no sqlite PDO driver';
            return;
        }
        if (!class_exists('Horde_Db_Adapter_Pdo_Sqlite')) {
            self::$reason = 'Horde_Db_Adapter_Pdo_Sqlite not available';
            return;
        }
        try {
            self::$db = new Horde_Db_Adapter_Pdo_Sqlite([
                'dbname' => ':memory:',
                'charset' => 'utf-8',
            ]);
            parent::setUpBeforeClass();
        } catch (Exception $e) {
            self::$reason = 'Sqlite not available: ' . $e->getMessage();
        }
    }
}
