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
use Horde_Vfs;
use Horde_Vfs_Base;
use Horde_Vfs_Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Vfs_Base::class)]
class BaseQuotaTest extends TestCase
{
    public function testSetQuotaByte(): void
    {
        $vfs = new VfsBaseExposed();
        $vfs->setQuota(100);
        $this->assertSame(100, $vfs->getParam('vfs_quotalimit'));
    }

    public function testSetQuotaKB(): void
    {
        $vfs = new VfsBaseExposed();
        $vfs->setQuota(1, Horde_Vfs::QUOTA_METRIC_KB);
        $this->assertSame(1024, $vfs->getParam('vfs_quotalimit'));
    }

    public function testSetQuotaMB(): void
    {
        $vfs = new VfsBaseExposed();
        $vfs->setQuota(1, Horde_Vfs::QUOTA_METRIC_MB);
        $this->assertSame(1048576, $vfs->getParam('vfs_quotalimit'));
    }

    public function testSetQuotaGB(): void
    {
        $vfs = new VfsBaseExposed();
        $vfs->setQuota(1, Horde_Vfs::QUOTA_METRIC_GB);
        $this->assertSame(1073741824, $vfs->getParam('vfs_quotalimit'));
    }

    public function testGetQuotaThrowsWhenNotSet(): void
    {
        $vfs = new VfsBaseExposed();
        $this->expectException(Horde_Vfs_Exception::class);
        $vfs->getQuota();
    }

    public function testSetQuotaRoot(): void
    {
        $vfs = new VfsBaseExposed();
        $vfs->setQuotaRoot('/data');
        $this->assertSame('/data', $vfs->getParam('vfs_quotaroot'));
    }

    public function testDefaultQuotaLimitIsNegativeOne(): void
    {
        $vfs = new VfsBaseExposed();
        $this->assertSame(-1, $vfs->getParam('vfs_quotalimit'));
    }

    public function testDefaultQuotaRootIsEmptyString(): void
    {
        $vfs = new VfsBaseExposed();
        $this->assertSame('', $vfs->getParam('vfs_quotaroot'));
    }
}
