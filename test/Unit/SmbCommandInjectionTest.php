<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Vfs\Test\Unit;

use Horde_Vfs_Smb;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for HF-01 — OS command injection through user-controlled
 * filenames handed to smbclient.
 *
 * Reported 2026-07-01 by Matthew Hickey ("Hacker Fantastic"). Root cause:
 * Horde_Vfs_Smb::_escapeShellCommand() only escaped ';' and '\\' before
 * concatenating filenames into a shell command that was run through /bin/sh
 * by proc_open(). Shell metacharacters $(...), backtick, ", newline, |, &
 * survived and executed as commands.
 *
 * The reproducer uses two independent oracles:
 *
 *   1. Structural — capture the value that _command() would hand to
 *      proc_open(). After the fix this must be an argv-style array (proc_open
 *      then calls execvp() and no shell is involved). Before the fix it is a
 *      single string containing the raw filename.
 *
 *   2. Behavioural — mirror the reporter's PoC harness. Point 'smbclient' at
 *      /bin/echo and let _command() actually run. If a shell parses the
 *      command, $(touch <marker>) inside a filename creates the marker file;
 *      argv-based proc_open() does not.
 *
 * Both oracles must hold on every public method that names a file or folder.
 */
#[CoversClass(Horde_Vfs_Smb::class)]
class SmbCommandInjectionTest extends TestCase
{
    /**
     * A payload built to be dangerous only when interpreted by /bin/sh.
     * $(touch <marker>) inside double quotes executes command substitution
     * before smbclient (or echo) ever sees the argument. Backtick would do
     * the same; we pick $(...) because the reporter's PoC used the same
     * form and because bash and dash both honour it.
     */
    private function payloadCreating(string $marker): string
    {
        return 'safe$(touch ' . $marker . ').txt';
    }

    private function makeMarkerPath(string $tag): string
    {
        return sys_get_temp_dir() . '/hf01_' . $tag . '_' . bin2hex(random_bytes(6));
    }

    /**
     * Concrete subclass that intercepts _execute() and remembers what
     * _command() built. Nothing is actually run.
     */
    private function newCaptureVfs(): CapturingSmb
    {
        return new CapturingSmb([
            'username'  => 'user',
            'password'  => 'pw',
            'hostspec'  => 'host',
            'share'     => 'share',
            'smbclient' => '/bin/echo',
            'vfsroot'   => '/vfs',
        ]);
    }

    /**
     * Concrete subclass that actually runs _execute() but points smbclient
     * at /bin/echo so no SMB server is required. This is the reporter's
     * PoC pattern packaged as a unit test.
     */
    private function newRealisticVfs(): RealisticSmb
    {
        return new RealisticSmb([
            'username'  => 'user',
            'password'  => 'pw',
            'hostspec'  => 'host',
            'share'     => 'share',
            'smbclient' => '/bin/echo',
            'vfsroot'   => '/vfs',
        ]);
    }

    /**
     * @return array<string, array{0: callable(Horde_Vfs_Smb, string): void}>
     *
     * Each row is (label, driver-callable) where driver-callable takes the
     * vfs instance and the attacker-controlled filename and performs the
     * VFS operation. Every mutating/reading method that assembles a
     * smbclient command must be covered.
     */
    public static function attackSurfaces(): array
    {
        return [
            'createFolder'  => [static fn ($vfs, $name) => $vfs->createFolder('', $name)],
            'deleteFile'    => [static fn ($vfs, $name) => $vfs->deleteFile('', $name)],
            'rename'        => [static fn ($vfs, $name) => $vfs->rename('', 'old', '', $name)],
            'readFile'      => [static fn ($vfs, $name) => @$vfs->readFile('', $name)],
            'write'         => [static function ($vfs, $name): void {
                $tmp = tempnam(sys_get_temp_dir(), 'hf01_src');
                try {
                    $vfs->write('', $name, $tmp);
                } finally {
                    @unlink($tmp);
                }
            }],
            'isFolder'      => [static fn ($vfs, $name) => $vfs->isFolder('', $name)],
            'listFolderPath' => [static fn ($vfs, $name) => @$vfs->listFolder($name)],
        ];
    }

    /**
     * Structural oracle: after the fix, _execute() must be handed an argv
     * array. Every element is a separate argument, so the attacker payload
     * never enters a shell parse. Before the fix, it is a single string and
     * this assertion fails.
     *
     * We rebuild rootCreated to skip the _createRoot() dance for methods
     * that would otherwise fire extra _command() calls (the last-call check
     * inspects only the operation under test).
     */
    #[DataProvider('attackSurfaces')]
    public function testCommandArgumentIsArgvArray(callable $driver): void
    {
        $vfs = $this->newCaptureVfs();
        $driver($vfs, 'safe.txt');

        $last = $vfs->lastExecuteArg;
        $this->assertNotNull($last, 'operation did not invoke _execute()');
        $this->assertIsArray(
            $last,
            'HF-01: _command() handed a shell string to proc_open() — must be an argv array so no /bin/sh is invoked'
        );
        // Sanity: the first element must be the smbclient binary itself,
        // not shell arguments, quoting, or a concatenated command.
        $this->assertSame('/bin/echo', $last[0], 'argv[0] must be the smbclient binary');
    }

    /**
     * Behavioural oracle — the reporter's PoC as an assertion.
     *
     * Point smbclient at /bin/echo. If any layer between us and execve()
     * hands the payload to /bin/sh, $(touch <marker>) creates the marker
     * file. If everything uses argv-style exec, the marker does not appear.
     */
    #[DataProvider('attackSurfaces')]
    public function testFilenamePayloadDoesNotFireCommandSubstitution(callable $driver): void
    {
        $marker = $this->makeMarkerPath('cmdsubst');
        try {
            $vfs = $this->newRealisticVfs();
            try {
                $driver($vfs, $this->payloadCreating($marker));
            } catch (\Throwable $e) {
                // We do not care if the VFS operation reports failure — echo
                // is not smbclient, so the parseListing/exit-code path will
                // often throw. We only care whether the payload fired.
            }

            $this->assertFileDoesNotExist(
                $marker,
                'HF-01: $(touch ...) in a filename executed — command substitution reached /bin/sh'
            );
        } finally {
            @unlink($marker);
        }
    }

    /**
     * Same behavioural check for backtick command substitution, which the
     * reporter's original disclosure highlighted alongside $(...).
     */
    #[DataProvider('attackSurfaces')]
    public function testBacktickPayloadDoesNotFireCommandSubstitution(callable $driver): void
    {
        $marker = $this->makeMarkerPath('backtick');
        try {
            $vfs = $this->newRealisticVfs();
            try {
                $driver($vfs, 'safe`touch ' . $marker . '`.txt');
            } catch (\Throwable $e) {
                // ignore — see above
            }

            $this->assertFileDoesNotExist(
                $marker,
                'HF-01: `touch ...` in a filename executed — backtick command substitution reached /bin/sh'
            );
        } finally {
            @unlink($marker);
        }
    }

    /**
     * Newline in a filename must not let the attacker chain a second shell
     * command onto the smbclient invocation.
     */
    #[DataProvider('attackSurfaces')]
    public function testNewlinePayloadDoesNotChainCommands(callable $driver): void
    {
        $marker = $this->makeMarkerPath('newline');
        try {
            $vfs = $this->newRealisticVfs();
            try {
                $driver($vfs, "safe.txt\ntouch " . $marker);
            } catch (\Throwable $e) {
                // ignore
            }

            $this->assertFileDoesNotExist(
                $marker,
                'HF-01: newline in filename let attacker chain a second shell command'
            );
        } finally {
            @unlink($marker);
        }
    }

    /**
     * Config-derived interpolations. The reporter's write-up covers only
     * the request-derived filename sink, but the same shell-string
     * assembly interpolates $hostspec, $username, $ipaddress, $domain,
     * $port and $share without escaping. A hostile or careless
     * conf.php should not turn into RCE.
     */
    public function testHostspecFromConfigDoesNotFireCommandSubstitution(): void
    {
        $marker = $this->makeMarkerPath('hostspec');
        try {
            $vfs = new RealisticSmb([
                'username'  => 'user',
                'password'  => 'pw',
                'hostspec'  => 'host$(touch ' . $marker . ')',
                'share'     => 'share',
                'smbclient' => '/bin/echo',
                'vfsroot'   => '/vfs',
            ]);
            try {
                $vfs->createFolder('', 'safe');
            } catch (\Throwable $e) {
                // ignore
            }

            $this->assertFileDoesNotExist(
                $marker,
                'HF-01: hostspec config value reached /bin/sh — RCE via conf.php'
            );
        } finally {
            @unlink($marker);
        }
    }
}

/**
 * Capturing subclass — records the argument _execute() would receive and
 * returns a plausible smbclient success response so the caller can
 * continue.
 */
class CapturingSmb extends Horde_Vfs_Smb
{
    /** @var mixed */
    public $lastExecuteArg = null;

    public function __construct(array $params)
    {
        parent::__construct($params);
        // Skip the vfsroot bootstrap — every operation would otherwise
        // fire extra _command() calls that overwrite lastExecuteArg with
        // mkdir noise. The point of this fixture is to inspect the
        // operation under test, not the root-creation preamble.
        $this->_rootCreated = true;
    }

    protected function _execute($cmd)
    {
        $this->lastExecuteArg = $cmd;
        // Return output that satisfies the various post-processing paths:
        //  - readFile checks that the local temp file exists (we can't
        //    fake that here — the readFile test tolerates a thrown
        //    exception, we still capture the arg).
        //  - listFolder feeds the output through parseListing which is
        //    happy with an empty array.
        return [];
    }
}

/**
 * Realistic subclass — leaves _execute() alone so proc_open() actually
 * runs. smbclient is pointed at /bin/echo in the params so no SMB server
 * is required. If any layer invokes /bin/sh, the shell parses the
 * command line and the attacker payload fires; we assert this does not
 * happen.
 */
class RealisticSmb extends Horde_Vfs_Smb
{
    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->_rootCreated = true;
    }
}
