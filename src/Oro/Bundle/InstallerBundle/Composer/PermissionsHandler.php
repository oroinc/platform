<?php

namespace Oro\Bundle\InstallerBundle\Composer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Set necessary access permissions for files.
 */
class PermissionsHandler
{
    public const SETFACL = 'setfacl -Rm "u:{user}:rwX,d:u:{user}:rwX,g:{group}:rw,d:g:{group}:rw" {path}';
    public const CHMOD   = 'chmod +a "{user} allow delete,write,append,file_inherit,directory_inherit" {path}';
    public const PS_AUX =
        "ps aux|grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx'|grep -v root|head -1|cut -d' ' -f1";

    public const USER = '`whoami`';

    public const VAR_PATH  = '{path}';
    public const VAR_USER  = '{user}';
    public const VAR_GROUP = '{group}';

    /**
     * @param string $directory
     * @return bool
     */
    public function setPermissions($directory)
    {
        // Check ACL support before attempting to use it
        if (!$this->isAclSupported()) {
            return $this->setPermissionsTraditional($directory);
        }

        try {
            $this->setPermissionsSetfacl($directory);
            $this->setPermissionsChmod($directory);

            return true;
        } catch (ProcessFailedException $exception) {
            // Fall back to traditional permissions if ACL fails
            return $this->setPermissionsTraditional($directory);
        }
    }

    /**
     * Check if ACL is supported on the filesystem
     *
     * @return bool
     */
    protected function isAclSupported(): bool
    {
        static $aclSupported = null;

        if ($aclSupported !== null) {
            return $aclSupported;
        }

        $fs = new Filesystem();
        $testDir = 'var/cache';
        if (!$fs->exists($testDir)) {
            $fs->mkdir($testDir, 0755);
        }

        $testFile = $testDir . '/.acl_test_' . uniqid();

        try {
            $fs->touch($testFile);
            $user = trim(shell_exec('whoami') ?: 'www-data');

            $testCmd = sprintf('setfacl -m "u:%s:rw" %s 2>&1', escapeshellarg($user), escapeshellarg($testFile));
            $process = $this->getProcess($testCmd);
            $process->setTimeout(2);
            $process->run();

            if ($process->isSuccessful()) {
                $aclSupported = true;
            } else {
                $output = $process->getErrorOutput() . $process->getOutput();
                $aclSupported = stripos($output, 'Operation not supported') === false
                    && stripos($output, 'not supported') === false;
            }

            if ($fs->exists($testFile)) {
                $fs->remove($testFile);
            }
        } catch (\Exception $e) {
            $aclSupported = false;
            if ($fs->exists($testFile)) {
                try {
                    $fs->remove($testFile);
                } catch (\Exception $e2) {
                    // Ignore cleanup errors
                }
            }
        }

        return $aclSupported;
    }

    /**
     * Set permissions using traditional Unix chmod/chown when ACL is not supported
     *
     * @param string $directory
     * @return bool
     */
    protected function setPermissionsTraditional($directory): bool
    {
        $fs = new Filesystem();
        if (!$fs->exists($directory)) {
            $fs->mkdir($directory);
        }

        try {
            $user = trim(shell_exec('whoami') ?: 'www-data');
            $group = $user;

            $webServerUser = $this->runProcessQuiet(self::PS_AUX);
            if ($webServerUser) {
                $group = $webServerUser;
            }

            $chownCmd = sprintf('chown -R %s:%s %s', escapeshellarg($user), escapeshellarg($group), escapeshellarg($directory));
            $process = $this->getProcess($chownCmd);
            $process->run();

            $chmodCmd = sprintf('find %s -type d -exec chmod 775 {} +', escapeshellarg($directory));
            $process = $this->getProcess($chmodCmd);
            $process->run();

            $chmodCmd = sprintf('find %s -type f -exec chmod 664 {} +', escapeshellarg($directory));
            $process = $this->getProcess($chmodCmd);
            $process->run();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $path
     * @throws \InvalidArgumentException
     */
    public function setPermissionsSetfacl($path)
    {
        if (!$this->isAclSupported()) {
            throw new ProcessFailedException(new Process(['setfacl'], null, null, null, 0));
        }

        $fs = new Filesystem();
        if (!$fs->exists($path)) {
            $fs->mkdir($path);
        }

        foreach ($this->getUsers() as $user) {
            $this->runProcess(
                str_replace([self::VAR_USER, self::VAR_GROUP, self::VAR_PATH], [$user, $user, $path], self::SETFACL)
            );
        }
    }

    /**
     * @param string $path
     * @throws \InvalidArgumentException
     */
    public function setPermissionsChmod($path)
    {
        $fs = new Filesystem();
        if (!$fs->exists($path)) {
            $fs->mkdir($path);
        }

        foreach ($this->getUsers() as $user) {
            try {
                $this->runProcess(
                    str_replace([self::VAR_USER, self::VAR_PATH], [$user, $path], self::CHMOD)
                );
            } catch (ProcessFailedException $e) {
                // chmod +a might not be supported, skip it
                continue;
            }
        }
    }

    /**
     * @return array
     */
    protected function getUsers()
    {
        $users = [self::USER];

        if ($webServerUser = $this->runProcess(self::PS_AUX)) {
            $users[] = $webServerUser;
        }

        return $users;
    }

    /**
     * @param string $commandline
     * @return string
     * @throws ProcessFailedException
     */
    protected function runProcess($commandline)
    {
        $process = $this->getProcess($commandline);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }

    /**
     * Run process without throwing exception on failure
     *
     * @param string $commandline
     * @return string|null
     */
    protected function runProcessQuiet($commandline)
    {
        try {
            return $this->runProcess($commandline);
        } catch (ProcessFailedException $e) {
            return null;
        }
    }

    protected function getProcess(string $commandline): Process
    {
        if (method_exists(Process::class, 'fromShellCommandline')) {
            return Process::fromShellCommandline($commandline);
        } else {
            return new Process($commandline);
        }
    }
}

