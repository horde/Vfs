<?php

/**
 * Copyright 2014-2026 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Vfs\Test\Integration\Sql;

use Horde\Vfs\Test\Integration\ConfigHelper;
use Horde_Db_Adapter_Oci8;

/**
 * @coversNothing
 */
class Oci8Test extends BaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('oci8')) {
            self::$reason = 'No oci8 extension';
            return;
        }
        $config = ConfigHelper::getConfig(
            'VFS_SQL_OCI8_TEST_CONFIG',
            __DIR__ . '/../..'
        );
        if ($config && !empty($config['vfs']['sql']['oci8'])) {
            self::$db = new Horde_Db_Adapter_Oci8($config['vfs']['sql']['oci8']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No oci8 configuration';
        }
    }

    public function testWriteLargeData(): void
    {
        // Write twice to test both INSERT and UPDATE.
        self::$vfs->writeData('', 'large', str_repeat('x', 4001));
        self::$vfs->writeData('', 'large', str_repeat('x', 4001));
        self::$vfs->deleteFile('', 'large');
    }
}
