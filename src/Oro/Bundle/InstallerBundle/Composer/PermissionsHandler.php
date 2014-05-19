<?php

namespace Oro\Bundle\InstallerBundle\Composer;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PermissionsHandler
{
    const SETFACL  = 'setfacl -R -m u:"%s":rwX %s';
    const SETFACLD = 'setfacl -dR -m u:"%s":rwX %s';
    const CHMOD    = 'chmod +a "%s allow delete,write,append,file_inherit,directory_inherit" %s';
    const PS_AUX   = "ps aux|grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx'|grep -v root|head -1|cut -d' ' -f1";

    const USER_COMMAND = '`whoami`';

    /**
     * @var Process
     */
    protected $process;

    /**
     * @param Process $process
     */
    public function setProcess(Process $process)
    {
        $this->process = $process;
    }

    /**
     * @param string $directory
     * @return bool
     */
    public function setPermissions($directory)
    {
        try {
            $this->setPermissionsSetfacl($directory);

            return true;
        } catch (ProcessFailedException $setfaclException) {
        }

        try {
            self::setPermissionsChmod($directory);

            return true;
        } catch (ProcessFailedException $chmodException) {
        }

        return false;
    }

    /**
     * @param string $path
     * @throws \InvalidArgumentException
     */
    public function setPermissionsSetfacl($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException($path);
        }

        if ($httpdUser = $this->getHttpdUser()) {
            $this->runProcess(sprintf(self::SETFACL, $httpdUser, $path));
            $this->runProcess(sprintf(self::SETFACLD, $httpdUser, $path));
        }
        $this->runProcess(sprintf(self::SETFACL, self::USER_COMMAND, $path));
        $this->runProcess(sprintf(self::SETFACLD, self::USER_COMMAND, $path));
    }

    /**
     * @param string $path
     * @throws \InvalidArgumentException
     */
    public function setPermissionsChmod($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException($path);
        }

        if ($httpdUser = $this->getHttpdUser()) {
            $this->runProcess(sprintf(self::CHMOD, $httpdUser, $path));
        }
        $this->runProcess(sprintf(self::CHMOD, self::USER_COMMAND, $path));
    }

    /**
     * @return string
     */
    protected function getHttpdUser()
    {
        return $this->runProcess(self::PS_AUX);
    }

    /**
     * @param string $commandline
     * @return string
     * @throws ProcessFailedException
     */
    protected function runProcess($commandline)
    {
        if (null === $this->process) {
            $this->process = new Process(null);
        }

        $this->process->setCommandLine($commandline);
        $this->process->run();
        if (!$this->process->isSuccessful()) {
            throw new ProcessFailedException($this->process);
        }

        return trim($this->process->getOutput());
    }
}
