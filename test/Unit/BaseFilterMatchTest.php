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
class BaseFilterMatchTest extends TestCase
{
    private VfsBaseExposed $vfs;

    protected function setUp(): void
    {
        $this->vfs = new VfsBaseExposed();
    }

    public function testNullFilterReturnsNoMatch(): void
    {
        $this->assertFalse($this->vfs->filterMatch(null, 'anyfile'));
    }

    public function testEmptyStringFilterReturnsNoMatch(): void
    {
        $this->assertFalse($this->vfs->filterMatch('', 'anyfile'));
    }

    public function testRegexFilterMatches(): void
    {
        $this->assertTrue($this->vfs->filterMatch('^test', 'testfile'));
    }

    public function testRegexFilterNoMatch(): void
    {
        $this->assertFalse($this->vfs->filterMatch('^test', 'other'));
    }

    public function testArrayFilterCombinesWithOr(): void
    {
        $this->assertTrue($this->vfs->filterMatch(['foo', 'bar'], 'foofile'));
        $this->assertTrue($this->vfs->filterMatch(['foo', 'bar'], 'barfile'));
        $this->assertFalse($this->vfs->filterMatch(['foo', 'bar'], 'bazfile'));
    }

    public function testComplexRegexFilter(): void
    {
        $this->assertTrue($this->vfs->filterMatch('^.*1$', 'file1'));
        $this->assertFalse($this->vfs->filterMatch('^.*1$', 'file2'));
    }
}
