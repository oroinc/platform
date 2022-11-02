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
    const SETFACL = 'setfacl -Rm "u:{user}:rwX,d:u:{user}:rwX,g:{group}:rw,d:g:{group}:rw" {path}';
    const CHMOD   = 'chmod +a "{user} allow delete,write,append,file_inherit,directory_inherit" {path}';
    const PS_AUX  = "ps aux|grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx'|grep -v root|head -1|cut -d' ' -f1";

    const USER = '`whoami`';

    const VAR_PATH  = '{path}';
    const VAR_USER  = '{user}';
    const VAR_GROUP = '{group}';

    /**
     * @param string $directory
     * @return bool
     */
    public function setPermissions($directory)
    {
        try {
            $this->setPermissionsSetfacl($directory);
            $this->setPermissionsChmod($directory);

            return true;
        } catch (ProcessFailedException $exception) {
        }

        return false;
    }

    /**
     * @param string $path
     * @throws \InvalidArgumentException
     */
    public function setPermissionsSetfacl($path)
    {
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
            $this->runProcess(
                str_replace([self::VAR_USER, self::VAR_PATH], [$user, $path], self::CHMOD)
            );
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

    protected function getProcess(string $commandline): Process
    {
        if (method_exists(Process::class, 'fromShellCommandline')) {
            return Process::fromShellCommandline($commandline);
        } else {
            return new Process($commandline);
        }
    }
}
