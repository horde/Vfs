<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Unit;

use Horde_Vfs_Smb;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Vfs_Smb::class)]
class SmbParseListingTest extends TestCase
{
    private string $oldTimezone;

    protected function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTimezone);
    }

    public function testParseSamba1Fixture(): void
    {
        $vfs = new Horde_Vfs_Smb();

        $listing = $vfs->parseListing(
            file(__DIR__ . '/../fixtures/samba1.txt'),
            null,
            true,
            false
        );
        $this->assertIsArray($listing);
        $this->assertCount(7, $listing);
        $this->assertSame(
            [
                'SystemHiddenReadonlyArchive' => [
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'SystemHiddenReadonlyArchive',
                    'type' => '**dir',
                    'date' => 1243426641,
                    'size' => -1,
                ],
                'Ein ziemlich langer Ordner mit vielen Buchstaben, der nicht kurz ist' => [
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Ein ziemlich langer Ordner mit vielen Buchstaben, der nicht kurz ist',
                    'type' => '**dir',
                    'date' => 1243426451,
                    'size' => -1,
                ],
                'Eine ziemlich lange Datei mit vielen Buchstaben, die nicht kurz ist.txt' => [
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Eine ziemlich lange Datei mit vielen Buchstaben, die nicht kurz ist.txt',
                    'type' => 'txt',
                    'date' => 1243426482,
                    'size' => '0',
                ],
                'Ordner mit Sonderzeichen & ( ) _ - toll' => [
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Ordner mit Sonderzeichen & ( ) _ - toll',
                    'type' => '**dir',
                    'date' => 1243426505,
                    'size' => -1,
                ],
                'Datei mit SOnderzeichen ¿ € § µ ° juhuuu.txt' => [
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Datei mit SOnderzeichen ¿ € § µ ° juhuuu.txt',
                    'type' => 'txt',
                    'date' => 1243426538,
                    'size' => '0',
                ],
                'SystemHiddenReadonlyArchive.txt' => [
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'SystemHiddenReadonlyArchive.txt',
                    'type' => 'txt',
                    'date' => 1243426592,
                    'size' => '0',
                ],
                'SystemHiddenReadonlyArchive.txte' => [
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'SystemHiddenReadonlyArchive.txte',
                    'type' => 'txte',
                    'date' => 1243430322,
                    'size' => '31',
                ],
            ],
            $listing
        );
    }

    public function testParseSamba2Fixture(): void
    {
        $vfs = new Horde_Vfs_Smb();

        $listing = $vfs->parseListing(
            file(__DIR__ . '/../fixtures/samba2.txt'),
            null,
            true,
            false
        );
        $this->assertIsArray($listing);
        $this->assertCount(26, $listing);
        $this->assertArrayHasKey('tmp', $listing);
        $this->assertSame('**dir', $listing['tmp']['type']);
        $this->assertArrayHasKey('Der Fischer und seine Frau Märchen.odt', $listing);
        $this->assertSame('22935', $listing['Der Fischer und seine Frau Märchen.odt']['size']);
        $this->assertArrayHasKey('.DS_Store', $listing);
        $this->assertArrayHasKey('Gartenkonzept SZOE.doc', $listing);
        $this->assertSame('32959488', $listing['Gartenkonzept SZOE.doc']['size']);
    }
}
