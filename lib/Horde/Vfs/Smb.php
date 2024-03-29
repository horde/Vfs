<?php
/**
 * Codebase copyright 2002 Paul Gareau <paul@xhawk.net>.  Adapted with
 * permission by Patrice Levesque <wayne@ptaff.ca> from phpsmb-0.8 code, and
 * converted to the LGPL.  Please do not taunt original author, contact
 * Patrice Levesque or dev@lists.horde.org.
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Paul Gareau <paul@xhawk.net>
 * @author  Patrice Levesque <wayne@ptaff.ca>
 * @package Vfs
 * @todo    Add driver for smbclient extension https://github.com/eduardok/libsmbclient-php
 */

/**
 * Stateless VFS implementation for a SMB server, based on smbclient.
 *
 * Required values for $params:
 *  - username:  (string) The username with which to connect to the SMB server.
 *  - password:  (string) The password with which to connect to the SMB server.
 *  - hostspec:  (string) The SMB server to connect to.
 *  - share:     (string) The share to access on the SMB server. Any trailing
 *               paths will removed from the share and prepended to each path
 *               in further requests. Example: a share of 'myshare/basedir' and
 *               a request to 'dir/subdir' will result in a request to
 *               'basedir/dir/subdir' on myshare.
 *  - smbclient: (string) The path to the 'smbclient' executable.
 *
 * Optional values for $params:
 *  - port:      (integer) The SMB port number to connect to.
 *  - ipaddress: (string) The address of the server to connect to.
 *
 * Functions not implemented:
 *  - changePermissions(): The SMB permission style does not fit with the
 *                         module.
 *
 * All paths need to use forward slashes!
 *
 * @author  Paul Gareau <paul@xhawk.net>
 * @author  Patrice Levesque <wayne@ptaff.ca>
 * @package Vfs
 */
class Horde_Vfs_Smb extends Horde_Vfs_Base
{
    /**
     * List of additional credentials required for this VFS backend.
     *
     * @var array
     */
    protected $_credentials = array('username', 'password');

    /**
     * Prefix to use for every path.
     *
     * Passed as a path suffix to the share parameter.
     *
     * @var string
     */
    protected $_prefix = '';

    /**
     * Has the vfsroot already been created?
     *
     * @var boolean
     */
    protected $_rootCreated = false;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
        if (!isset($this->_params['share'])) {
            return;
        }
        $share_parts = explode('/', $this->_params['share']);
        $this->_params['share'] = array_shift($share_parts);
        if ($share_parts) {
            $this->_prefix = implode('/', $share_parts);
        }
    }

    /**
     * Retrieves the size of a file from the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return integer  The file size.
     * @throws Horde_Vfs_Exception
     */
    public function size($path, $name)
    {
        $file = $this->readFile($path, $name);
        return filesize($file);
    }

    /**
     * Retrieves a file from the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string  The file data.
     */
    public function read($path, $name)
    {
        $file = $this->readFile($path, $name);
        $size = filesize($file);
        return ($size === 0)
            ? ''
            : file_get_contents($file);
    }

    /**
     * Retrieves a file from the VFS as an on-disk local file.
     *
     * This function provides a file on local disk with the data of a VFS file
     * in it. This file <em>cannot</em> be modified! The behavior if you do
     * modify it is undefined. It will be removed at the end of the request.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string  A local filename.
     */
    public function readFile($path, $name)
    {
        // Create a temporary file and register it for deletion at the
        // end of this request.
        if (!($localFile = Horde_Util::getTempFile('vfs'))) {
            throw new Horde_Vfs_Exception('Unable to create temporary file.');
        }

        $this->_createRoot();

        list($npath, $name) = $this->_escapeShellCommand($this->_getNativePath($path), $name);
        $cmd = array('get \"' . $name . '\" ' . $localFile);
        $this->_command($npath, $cmd);
        if (!file_exists($localFile)) {
            throw new Horde_Vfs_Exception(sprintf('Unable to open VFS file "%s".', $this->_getPath($path, $name)));
        }

        clearstatcache();

        return $localFile;
    }

    /**
     * Open a stream to a file in the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return resource  The stream.
     */
    public function readStream($path, $name)
    {
        return fopen($this->readFile($path, $name),
                     substr(PHP_OS, 0, 3) == 'WIN' ? 'rb' : 'r');
    }

    /**
     * Stores a file in the VFS.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param string $tmpFile      The temporary file containing the data to be
     *                             stored.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws Horde_Vfs_Exception
     */
    public function write($path, $name, $tmpFile, $autocreate = false)
    {
        $this->_createRoot();

        // Double quotes not allowed in SMB filename.
        $name = str_replace('"', "'", $name);

        list($npath, $name) = $this->_escapeShellCommand($this->_getNativePath($path), $name);
        $cmd = array('put \"' . $tmpFile . '\" \"' . $name . '\"');
        // do we need to first autocreate the directory?
        if ($autocreate) {
            $this->autocreatePath($path);
        }

        $this->_command($npath, $cmd);
    }

    /**
     * Stores a file in the VFS from raw data.
     *
     * @param string $path           The path to store the file in.
     * @param string $name           The filename to use.
     * @param string|resource $data  The data as a string or stream resource.
     *                               Resources allowed  @since  2.4.0
     * @param boolean $autocreate    Automatically create directories?
     *
     * @throws Horde_Vfs_Exception
     */
    public function writeData($path, $name, $data, $autocreate = false)
    {
        $tmpFile = Horde_Util::getTempFile('vfs');
        if (is_resource($data)) {
            rewind($data);
        }
        file_put_contents($tmpFile, $data);
        try {
            $this->write($path, $name, $tmpFile, $autocreate);
            unlink($tmpFile);
        } catch (Horde_Vfs_Exception $e) {
            unlink($tmpFile);
            throw $e;
        }
    }

    /**
     * Deletes a file from the VFS.
     *
     * @param string $path  The path to delete the file from.
     * @param string $name  The filename to use.
     *
     * @throws Horde_Vfs_Exception
     */
    public function deleteFile($path, $name)
    {
        $this->_createRoot();

        list($path, $name) = $this->_escapeShellCommand($this->_getNativePath($path), $name);
        $cmd = array('del \"' . $name . '\"');
        $this->_command($path, $cmd);
    }

    /**
     * Checks if a given pathname is a folder.
     *
     * @param string $path  The path to the folder.
     * @param string $name  The file or folder name.
     *
     * @return boolean  True if it is a folder, false otherwise.
     */
    public function isFolder($path, $name)
    {
        $this->_createRoot();

        list($path, $name) = $this->_escapeShellCommand($this->_getNativePath($path), $name);
        try {
            $this->_command($this->_getPath($path, $name), array('quit'));
            return true;
        } catch (Horde_Vfs_Exception $e) {
            return false;
        }
    }

    /**
     * Deletes a folder from the VFS.
     *
     * @param string $path        The path to delete the folder from.
     * @param string $name        The name of the folder to delete.
     * @param boolean $recursive  Force a recursive delete?
     *
     * @throws Horde_Vfs_Exception
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        $this->_createRoot();

        if (!$this->isFolder($path, $name)) {
            throw new Horde_Vfs_Exception(sprintf('"%s" is not a directory.', $path . '/' . $name));
        }

        $file_list = $this->listFolder($this->_getPath($path, $name));

        if ($file_list && !$recursive) {
            throw new Horde_Vfs_Exception(sprintf('Unable to delete "%s", the directory is not empty.', $this->_getPath($path, $name)));
        }

        foreach ($file_list as $file) {
            if ($file['type'] == '**dir') {
                $this->deleteFolder($this->_getPath($path, $name), $file['name'], $recursive);
            } else {
                $this->deleteFile($this->_getPath($path, $name), $file['name']);
            }
        }

        // Really delete the folder.
        list($npath, $name) = $this->_escapeShellCommand($this->_getNativePath($path), $name);
        $cmd = array('rmdir \"' . $name . '\"');

        try {
            $this->_command($npath, $cmd);
        } catch (Horde_Vfs_Exception $e) {
            throw new Horde_Vfs_Exception(sprintf('Unable to delete VFS folder "%s".', $this->_getPath($path, $name)));
        }
    }

    /**
     * Renames a file in the VFS.
     *
     * @param string $oldpath  The old path to the file.
     * @param string $oldname  The old filename.
     * @param string $newpath  The new path of the file.
     * @param string $newname  The new filename.
     *
     * @throws Horde_Vfs_Exception
     */
    public function rename($oldpath, $oldname, $newpath, $newname)
    {
        $this->_createRoot();
        $this->autocreatePath($newpath);

        // Double quotes not allowed in SMB filename. The '/' character should
        // also be removed from the beginning/end of the names.
        $oldname = str_replace('"', "'", trim($oldname, '/'));
        $newname = str_replace('"', "'", trim($newname, '/'));

        if (empty($oldname)) {
            throw new Horde_Vfs_Exception('Unable to rename VFS file to same name.');
        }

        /* If the path was not empty (i.e. the path is not the root path),
         * then add the trailing '/' character to path. */
        if (!empty($oldpath)) {
            $oldpath .= '/';
        }
        if (!empty($newpath)) {
            $newpath .= '/';
        }

        list($file, $name) = $this->_escapeShellCommand($oldname, $newname);
        $cmd = array(
            'rename \"'
            .  str_replace('/', '\\\\', $this->_getNativePath($oldpath))
            . $file . '\" \"'
            . str_replace('/', '\\\\', $this->_getNativePath($newpath))
            . $name . '\"'
        );

        try {
            $this->_command('', $cmd);
        } catch (Horde_Vfs_Exception $e) {
            throw new Horde_Vfs_Exception(sprintf('Unable to rename VFS file "%s".', $this->_getPath($oldpath, $oldname)));
        }
    }

    /**
     * Creates a folder on the VFS.
     *
     * @param string $path  The path of directory to create folder.
     * @param string $name  The name of the new folder.
     *
     * @throws Horde_Vfs_Exception
     */
    public function createFolder($path, $name)
    {
        $this->_createRoot();

        // Double quotes not allowed in SMB filename.
        $name = str_replace('"', "'", $name);

        list($dir, $mkdir) = $this->_escapeShellCommand($this->_getNativePath($path), $name);
        $cmd = array('mkdir \"' . $mkdir . '\"');

        try {
            $this->_command($dir, $cmd);
        } catch (Horde_Vfs_Exception $e) {
            throw new Horde_Vfs_Exception(sprintf('Unable to create VFS folder "%s".', $this->_getPath($path, $name)));
        }
    }

    /**
     * Returns a file list of the directory passed in.
     *
     * @param string $path          The path of the directory.
     * @param string|array $filter  Regular expression(s) to filter
     *                              file/directory name on.
     * @param boolean $dotfiles     Show dotfiles?
     * @param boolean $dironly      Show only directories?
     * @param boolean $recursive    Return all directory levels recursively?
     *
     * @return array  File list.
     * @throws Horde_Vfs_Exception
     */
    public function listFolder($path = '', $filter = null, $dotfiles = true,
                               $dironly = false, $recursive = false)
    {
        $this->_createRoot();
        list($path) = $this->_escapeShellCommand($this->_getNativePath($path));
        return $this->parseListing($this->_command($path, array('ls')),
                                   $filter,
                                   $dotfiles,
                                   $dironly);
    }

    /**
     */
    public function parseListing($res, $filter, $dotfiles, $dironly)
    {
        $num_lines = count($res);
        $files = array();
        for ($r = 0; $r < $num_lines; $r++) {
            // Match file listing.
            // One or multiple whitespace
            // followed by filename
            // Followed by one or multiple WS
            // Followed by zero, one or more attribute letters
            // Followed by one or multiple WS
            // Followed by possibly more statistics
            if (!preg_match('/^\s+(.+?)\s+(\w*)\s+(\d+)  (\w\w\w \w\w\w [ \d]\d \d\d:\d\d:\d\d \d\d\d\d)$/', $res[$r], $match)) {
                continue;
            }

            // If the file name isn't . or ..
            if ($match[1] == '.' || $match[1] == '..') {
                continue;
            }

            $my_name = $match[1];

            // Filter out dotfiles if they aren't wanted.
            if (!$dotfiles && substr($my_name, 0, 1) == '.') {
                continue;
            }

            $my_size = $match[3];
            $ext_name = explode('.', $my_name);

            if ((strpos($match[2], 'D') !== false)) {
                $my_type = '**dir';
                $my_size = -1;
            } else {
                $my_type = Horde_String::lower($ext_name[count($ext_name) - 1]);
            }
            $my_date = strtotime($match[4]);
            $filedata = array('owner' => '',
                              'group' => '',
                              'perms' => '',
                              'name' => $my_name,
                              'type' => $my_type,
                              'date' => $my_date,
                              'size' => $my_size);
            // watch for filters and dironly
            if ($this->_filterMatch($filter, $my_name)) {
                unset($file);
                continue;
            }
            if ($dironly && $my_type !== '**dir') {
                unset($file);
                continue;
            }

            $files[$filedata['name']] = $filedata;
        }

        return $files;
    }

    /**
     * Copies a file through the backend.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param string $dest         The destination of the file.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws Horde_Vfs_Exception
     */
    public function copy($path, $name, $dest, $autocreate = false)
    {
        $this->_checkDestination($path, $dest);

        if ($autocreate) {
            $this->autocreatePath($dest);
        }

        foreach ($this->listFolder($dest, null, true) as $file) {
            if ($file['name'] == $name) {
                throw new Horde_Vfs_Exception(sprintf('%s already exists.', $this->_getPath($dest, $name)));
            }
        }

        if ($this->isFolder($path, $name)) {
            $this->_copyRecursive($path, $name, $dest);
        } else {
            try {
                $this->write($dest, $name, $this->readFile($path, $name));
            } catch (Horde_Vfs_Exception $e) {
                throw new Horde_Vfs_Exception(sprintf('Copy failed: %s', $this->_getPath($dest, $name)));
            }
        }
    }

    /**
     * Moves a file through the backend.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param string $dest         The destination of the file.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws Horde_Vfs_Exception
     */
    public function move($path, $name, $dest, $autocreate = false)
    {
        $orig = $this->_getPath($path, $name);
        if (preg_match('|^' . preg_quote($orig) . '/?$|', $dest)) {
            throw new Horde_Vfs_Exception('Cannot copy file(s) - destination is within source.');
        }

        if ($autocreate) {
            $this->autocreatePath($dest);
        }

        foreach ($this->listFolder($dest, null, true) as $file) {
            if ($file['name'] == $name) {
                throw new Horde_Vfs_Exception(sprintf('%s already exists.', $this->_getPath($dest, $name)));
            }
        }

        try {
            $this->rename($path, $name, $dest, $name);
        } catch (Horde_Vfs_Exception $e) {
            throw new Horde_Vfs_Exception(sprintf('Failed to move to "%s".', $this->_getPath($dest, $name)));
        }
    }

    /**
     * Returns the full path of a directory.
     *
     * @param string $path  The directory.
     *
     * @return string  Full path to the directory.
     */
    protected function _getNativePath($path)
    {
        $parts = array($path);
        if (strlen($this->_prefix)) {
            array_unshift($parts, $this->_prefix);
        }
        if (isset($this->_params['vfsroot']) &&
            strlen($this->_params['vfsroot'])) {
            array_unshift($parts, $this->_params['vfsroot']);
        }
        $path = implode('/', $parts);

        // In some samba versions after samba-3.0.25-pre2, $path must
        // end in a trailing slash.
        if (substr($path, -1) != '/') {
            $path .= '/';
        }

        return $path;
    }

    /**
     * Replacement for escapeshellcmd(), variable length args, as we only want
     * certain characters escaped.
     *
     * @param array $array  Strings to escape.
     *
     * @return array  TODO
     */
    protected function _escapeShellCommand()
    {
        $ret = array();
        $args = func_get_args();
        foreach ($args as $arg) {
            $ret[] = str_replace(array(';', '\\'), array('\;', '\\\\'), $arg);
        }
        return $ret;
    }

    /**
     * Executes a command and returns output lines in array.
     *
     * @param string $cmd  Command to be executed.
     *
     * @return array  Array on success.
     * @throws Horde_Vfs_Exception
     */
    protected function _execute($cmd)
    {
        $cmd = str_replace('"-U%"', '-N', $cmd);
        $proc = proc_open(
            $cmd,
            array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
            $pipes);
        if (!is_resource($proc)) {
            // This should never happen.
            throw new Horde_Vfs_Exception('Failed to call proc_open().');
        }
        $out   = explode("\n", trim(stream_get_contents($pipes[1])));
        $error = explode("\n", trim(stream_get_contents($pipes[2])));
        $ret = proc_close($proc);

        // In some cases, (like trying to delete a nonexistant file),
        // smbclient will return success (at least on 2.2.7 version I'm
        // testing on). So try to match error strings, even after success.
        if ($ret != 0) {
            $err = '';
            foreach ($error as $line) {
                if (strpos($line, 'Usage:') === 0) {
                    $err = 'Command syntax incorrect';
                    break;
                }
                if (strpos($line, 'ERRSRV') !== false ||
                    strpos($line, 'ERRDOS') !== false) {
                    $err = preg_replace('/.*\((.+)\).*/', '\\1', $line);
                    if (!$err) {
                        $err = $line;
                    }
                    break;
                }
            }
            if (!$err) {
                $err = $out ? $out[count($out) - 1] : $ret;
            }

            throw new Horde_Vfs_Exception($err);
        }

        // Check for errors even on success.
        $err = '';
        foreach ($out as $line) {
            if (strpos($line, 'NT_STATUS_NO_SUCH_FILE') !== false ||
                strpos($line, 'NT_STATUS_OBJECT_NAME_NOT_FOUND') !== false) {
                $err = Horde_Vfs_Translation::t("No such file");
                break;
            } elseif (strpos($line, 'NT_STATUS_ACCESS_DENIED') !== false) {
                $err = Horde_Vfs_Translation::t("Permission Denied");
                break;
            }
        }

        if ($err) {
            throw new Horde_Vfs_Exception($err);
        }

        return $out;
    }

    /**
     * Executes SMB commands - without authentication - and returns output
     * lines in array.
     *
     * @param array $path  Base path for command.
     * @param array $cmd   Commands to be executed.
     *
     * @return array  Array on success.
     * @throws Horde_Vfs_Exception
     */
    protected function _command($path, $cmd)
    {
        list($share) = $this->_escapeShellCommand($this->_params['share']);

        putenv('PASSWD=' . $this->_params['password']);
        $port = isset($this->_params['port'])
            ? (' "-p' . $this->_params['port'] . '"')
            : '';
        $ipoption = isset($this->_params['ipaddress'])
            ? (' -I ' . $this->_params['ipaddress'])
            : '';
        $domain = isset($this->_params['domain'])
            ? (' -W ' . $this->_params['domain'])
            : '';
        $fullcmd = $this->_params['smbclient'] .
            ' "//' . $this->_params['hostspec'] . '/' . $share . '"' .
            $port .
            ' "-U' . $this->_params['username'] . '"' .
            ' -D "' . $path . '"' .
            $ipoption .
            $domain .
            ' -c "';
        foreach ($cmd as $c) {
            $fullcmd .= $c . ";";
        }
        $fullcmd .= '"';

        return $this->_execute($fullcmd);
    }

    /**
     * Authenticates a user on the SMB server and share.
     *
     * @throws Horde_Vfs_Exception
     */
    protected function _connect()
    {
        try {
            $this->_command('', array('quit'));
        } catch (Horde_Vfs_Exception $e) {
            throw new Horde_Vfs_Exception('Authentication to the SMB server failed.');
        }
    }

    /**
     * Creates the vfsroot.
     */
    protected function _createRoot()
    {
        if ($this->_rootCreated) {
            return;
        }

        $root = trim($this->_params['vfsroot'] . '/' . $this->_prefix, '/');
        $path = '';
        foreach (explode('/', $root) as $dir) {
            try {
                $this->_command($path . '/' . $dir . '/', array());
            } catch (Horde_Vfs_Exception $e) {
                try {
                    $this->_command('/' . $path . '/', array('mkdir \"' . $dir . '\"'));
                } catch (Horde_Vfs_Exception $e) {
                    echo $e;
                    throw new Horde_Vfs_Exception(sprintf('Unable to create VFS root directory "%s".', $this->_params['vfsroot']));
                }
            }
            $path .= '/' . $dir;
        }

        $this->_rootCreated = true;
    }
}
