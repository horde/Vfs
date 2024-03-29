<?php
/**
 * Test the SMB based virtual file system.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
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

class SmbTest extends TestBase
{
    public static function setUpBeforeClass(): void
    {
        $config = self::getConfig('VFS_SMB_TEST_CONFIG', __DIR__);
        if ($config && !empty($config['vfs']['smb'])) {
            if (!is_executable($config['vfs']['smb']['smbclient'])) {
                self::$reason = 'No executable smbclient';
                return;
            }
            self::$vfs = Horde_Vfs::factory('Smb', $config['vfs']['smb']);
        } else {
            self::$reason = 'No smb configuration';
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

    public function setUp(): void
    {
        parent::setUp();

        $this->_oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
    }

    public function tearDown(): void
    {
        date_default_timezone_set($this->_oldTimezone);
    }

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
        $this->markTestIncomplete();
        $this->_folderSize();
    }

    /**
     * @depends testWrite
     * @depends testWriteData
     */
    public function testVfsSize()
    {
        $this->markTestIncomplete();
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
        $this->markTestIncomplete();
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

    public function testHostspecWithPath()
    {
        self::$vfs->createFolder('', 'hostspectest');
        self::$vfs->createFolder('hostspectest', 'directory');
        self::$vfs->createFolder('hostspectest/directory', 'subdir');
        $config = self::getConfig('VFS_SMB_TEST_CONFIG', __DIR__);
        $config['vfs']['smb']['share'] .= '/hostspectest';
        $vfs = Horde_Vfs::factory('Smb', $config['vfs']['smb']);
        $this->assertEquals(
            array('subdir'),
            array_keys($vfs->listFolder('directory'))
        );
    }

    public function testParseListing()
    {
        $vfs = new Horde_Vfs_Smb();

        $listing = $vfs->parseListing(file(__DIR__ . '/fixtures/samba1.txt'), null, true, false);
        $this->assertInternalType('array', $listing);
        $this->assertEquals(7, count($listing));
        $this->assertEquals(
            array (
                'SystemHiddenReadonlyArchive' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'SystemHiddenReadonlyArchive',
                    'type' => '**dir',
                    'date' => 1243426641,
                    'size' => -1,
                    ),
                'Ein ziemlich langer Ordner mit vielen Buchstaben, der nicht kurz ist' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Ein ziemlich langer Ordner mit vielen Buchstaben, der nicht kurz ist',
                    'type' => '**dir',
                    'date' => 1243426451,
                    'size' => -1,
                    ),
                'Eine ziemlich lange Datei mit vielen Buchstaben, die nicht kurz ist.txt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Eine ziemlich lange Datei mit vielen Buchstaben, die nicht kurz ist.txt',
                    'type' => 'txt',
                    'date' => 1243426482,
                    'size' => '0',
                    ),
                'Ordner mit Sonderzeichen & ( ) _ - toll' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Ordner mit Sonderzeichen & ( ) _ - toll',
                    'type' => '**dir',
                    'date' => 1243426505,
                    'size' => -1,
                    ),
                'Datei mit SOnderzeichen ¿ € § µ ° juhuuu.txt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Datei mit SOnderzeichen ¿ € § µ ° juhuuu.txt',
                    'type' => 'txt',
                    'date' => 1243426538,
                    'size' => '0',
                    ),
                'SystemHiddenReadonlyArchive.txt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'SystemHiddenReadonlyArchive.txt',
                    'type' => 'txt',
                    'date' => 1243426592,
                    'size' => '0',
                    ),
                'SystemHiddenReadonlyArchive.txte' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'SystemHiddenReadonlyArchive.txte',
                    'type' => 'txte',
                    'date' => 1243430322,
                    'size' => '31',
                    ),
                ),
            $listing);

        $listing = $vfs->parseListing(file(__DIR__ . '/fixtures/samba2.txt'), null, true, false);
        $this->assertInternalType('array', $listing);
        $this->assertEquals(26, count($listing));
        $this->assertEquals(
            array (
                'tmp' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'tmp',
                    'type' => '**dir',
                    'date' => 1199697783,
                    'size' => -1,
                    ),
                'Der Fischer und seine Frau Märchen.odt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Der Fischer und seine Frau Märchen.odt',
                    'type' => 'odt',
                    'date' => 1169758536,
                    'size' => '22935',
                    ),
                'Tänze' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Tänze',
                    'type' => '**dir',
                    'date' => 1169756813,
                    'size' => -1,
                    ),
                'Availabilities+rates EE-Dateien' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates EE-Dateien',
                    'type' => '**dir',
                    'date' => 1126615613,
                    'size' => -1,
                    ),
                'Briefkopf.odt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Briefkopf.odt',
                    'type' => 'odt',
                    'date' => 1137753731,
                    'size' => '9564',
                    ),
                'Deckblatt.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Deckblatt.pdf',
                    'type' => 'pdf',
                    'date' => 1196284002,
                    'size' => '18027',
                    ),
                'Babymassage.sxw' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Babymassage.sxw',
                    'type' => 'sxw',
                    'date' => 1102376414,
                    'size' => '9228',
                    ),
                'Gutschein.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gutschein.pdf',
                    'type' => 'pdf',
                    'date' => 1168102242,
                    'size' => '10621',
                    ),
                'Die zertanzten Schuh.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Die zertanzten Schuh.pdf',
                    'type' => 'pdf',
                    'date' => 1169483565,
                    'size' => '257955',
                    ),
                'Flyer Im Takt.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Flyer Im Takt.pdf',
                    'type' => 'pdf',
                    'date' => 1169891684,
                    'size' => '42905',
                    ),
                'Availabilities+rates EE.doc' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates EE.doc',
                    'type' => 'doc',
                    'date' => 1124044046,
                    'size' => '1407488',
                    ),
                'Availabilities+rates EE.htm' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates EE.htm',
                    'type' => 'htm',
                    'date' => 1126615336,
                    'size' => '262588',
                    ),
                'tt0208m_.ttf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'tt0208m_.ttf',
                    'type' => 'ttf',
                    'date' => 1111250096,
                    'size' => '47004',
                    ),
                'Alte Dateien.zip' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Alte Dateien.zip',
                    'type' => 'zip',
                    'date' => 1179697912,
                    'size' => '5566512',
                    ),
                'Availabilities+rates SQ-Dateien' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates SQ-Dateien',
                    'type' => '**dir',
                    'date' => 1126615567,
                    'size' => -1,
                    ),
                'Bobath-Befund.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Bobath-Befund.pdf',
                    'type' => 'pdf',
                    'date' => 1196282600,
                    'size' => '123696',
                    ),
                'Availabilities+rates SQ.doc' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates SQ.doc',
                    'type' => 'doc',
                    'date' => 1124044062,
                    'size' => '109056',
                    ),
                'Availabilities+rates SQ.htm' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Availabilities+rates SQ.htm',
                    'type' => 'htm',
                    'date' => 1126615290,
                    'size' => '266079',
                    ),
                'tt0586m_.ttf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'tt0586m_.ttf',
                    'type' => 'ttf',
                    'date' => 1111250098,
                    'size' => '35928',
                    ),
                'Gartenkonzept SZOE.html' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gartenkonzept SZOE.html',
                    'type' => 'html',
                    'date' => 1199698030,
                    'size' => '168801',
                    ),
                '.DS_Store' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => '.DS_Store',
                    'type' => 'ds_store',
                    'date' => 1110391107,
                    'size' => '12292',
                    ),
                'Pfefferkuchenmann.odt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Pfefferkuchenmann.odt',
                    'type' => 'odt',
                    'date' => 1166644679,
                    'size' => '14399',
                    ),
                'Sockenstrickanleitung mit Bildern.sxw' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Sockenstrickanleitung mit Bildern.sxw',
                    'type' => 'sxw',
                    'date' => 1104172329,
                    'size' => '9518',
                    ),
                'Gartenkonzept SZOE.doc' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gartenkonzept SZOE.doc',
                    'type' => 'doc',
                    'date' => 1180365752,
                    'size' => '32959488',
                    ),
                'Gartenkonzept SZOE.odt' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gartenkonzept SZOE.odt',
                    'type' => 'odt',
                    'date' => 1180365528,
                    'size' => '32526103',
                    ),
                'Gartenkonzept SZOE.pdf' =>
                array (
                    'owner' => '',
                    'group' => '',
                    'perms' => '',
                    'name' => 'Gartenkonzept SZOE.pdf',
                    'type' => 'pdf',
                    'date' => 1179697180,
                    'size' => '32632182',
                    ),
                ),
            $listing);
    }

}
