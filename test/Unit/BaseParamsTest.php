<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Unit;

use Horde\Vfs\Test\Stub\VfsBaseExposed;
use Horde_Vfs_Base;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Vfs_Base::class)]
class BaseParamsTest extends TestCase
{
    public function testConstructorSetsParams(): void
    {
        $vfs = new VfsBaseExposed(['foo' => 'bar']);
        $this->assertSame('bar', $vfs->getParam('foo'));
    }

    public function testGetParamReturnsNullForMissing(): void
    {
        $vfs = new VfsBaseExposed();
        $this->assertNull($vfs->getParam('nonexistent'));
    }

    public function testSetParamsMerges(): void
    {
        $vfs = new VfsBaseExposed(['a' => '1']);
        $vfs->setParams(['b' => '2']);
        $this->assertSame('1', $vfs->getParam('a'));
        $this->assertSame('2', $vfs->getParam('b'));
    }

    public function testSetParamsOverwritesExisting(): void
    {
        $vfs = new VfsBaseExposed(['a' => '1']);
        $vfs->setParams(['a' => '2']);
        $this->assertSame('2', $vfs->getParam('a'));
    }

    public function testDefaultUserParamIsEmptyString(): void
    {
        $vfs = new VfsBaseExposed();
        $this->assertSame('', $vfs->getParam('user'));
    }

    public function testHasFeatureDefaultFalse(): void
    {
        $vfs = new VfsBaseExposed();
        $this->assertFalse($vfs->hasFeature('readByteRange'));
    }

    public function testHasFeatureReturnsTrue(): void
    {
        $vfs = new VfsBaseExposed();
        $vfs->setFeatures(['readByteRange' => true]);
        $this->assertTrue($vfs->hasFeature('readByteRange'));
    }

    public function testHasFeatureReturnsFalseForUnknown(): void
    {
        $vfs = new VfsBaseExposed();
        $this->assertFalse($vfs->hasFeature('nonexistent'));
    }

    public function testGetRequiredCredentialsEmptyByDefault(): void
    {
        $vfs = new VfsBaseExposed();
        $this->assertSame([], $vfs->getRequiredCredentials());
    }

    public function testGetRequiredCredentialsDiffsWithParams(): void
    {
        $vfs = new VfsBaseExposed(['username' => 'user']);
        $vfs->setCredentials(['username', 'password']);
        $result = $vfs->getRequiredCredentials();
        $this->assertContains('password', $result);
        $this->assertNotContains('username', $result);
    }

    public function testGetModifiablePermissions(): void
    {
        $vfs = new VfsBaseExposed();
        $perms = $vfs->getModifiablePermissions();
        $this->assertArrayHasKey('owner', $perms);
        $this->assertArrayHasKey('group', $perms);
        $this->assertArrayHasKey('all', $perms);
    }

    public function testGetModifiablePermissionsCustom(): void
    {
        $vfs = new VfsBaseExposed();
        $custom = ['owner' => ['read' => true]];
        $vfs->setPermissions($custom);
        $this->assertSame($custom, $vfs->getModifiablePermissions());
    }
}
