<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Unit;

use Horde_Vfs_Base;
use Horde_Vfs_Gc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Vfs_Gc::class)]
class GcTest extends TestCase
{
    /**
     * Most of the time (99.9%), gc() returns immediately because
     * substr(time(), -3) !== '000'. This test verifies that path:
     * the VFS mock should NOT have listFolder called.
     */
    public function testGcSkipsWhenTimeDoesNotEndIn000(): void
    {
        if (substr((string) time(), -3) === '000') {
            $this->markTestSkipped('Rare timing collision - time ends in 000');
        }

        $vfs = $this->createMock(Horde_Vfs_Base::class);
        $vfs->expects($this->never())->method('listFolder');

        Horde_Vfs_Gc::gc($vfs, '/tmp');
    }

    /**
     * When gc does fire (time ends in 000), it delegates to the VFS
     * backend's gc() method if available, then calls listFolder.
     *
     * This test is inherently probabilistic: it only fires when
     * substr(time(), -3) === '000'. We test the behavior indirectly
     * by verifying the class is callable and has the expected signature.
     */
    public function testGcMethodIsCallable(): void
    {
        $this->assertTrue(
            method_exists(Horde_Vfs_Gc::class, 'gc'),
            'Horde_Vfs_Gc::gc() method should exist'
        );

        $reflection = new \ReflectionMethod(Horde_Vfs_Gc::class, 'gc');
        $this->assertTrue($reflection->isStatic());
        $this->assertCount(3, $reflection->getParameters());
    }
}
