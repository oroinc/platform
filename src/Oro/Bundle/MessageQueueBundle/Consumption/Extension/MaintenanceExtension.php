<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Bundle\PlatformBundle\Maintenance\Mode;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class MaintenanceExtension extends AbstractExtension
{
    /** @var Mode */
    private $maintenance;

    /** @var string */
    private $filePathServerLock;

    /** @var int */
    private $idleTime;

    /**
     * @param Mode $maintenance The maintenance mode state
     * @param int  $idleTime    The sleep time in seconds between checks
     *                          whether the system is still in the maintenance mode
     */
    public function __construct(Mode $maintenance, $idleTime)
    {
        $this->maintenance = $maintenance;
        $this->idleTime = $idleTime;
    }

    /**
     * @param string $configurationFilePath
     */
    public function setFilePathServerLockFromConfig($configurationFilePath)
    {
        $lockFilePath = null;
        if (!file_exists($configurationFilePath)) {
            // configuration file path can be unavailable
            return;
        }
        $iniArray = parse_ini_file($configurationFilePath);
        if (isset($iniArray['LOCK_FILE'])) {
            $lockFilePath = $this->trimComment($iniArray['LOCK_FILE']);
            preg_match_all('/\$.\w+/', $lockFilePath, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $varName = current($match);
                $envVarName = str_replace('$', '', $varName);
                if (!isset($iniArray[$envVarName])) {
                    throw new \InvalidArgumentException(sprintf(
                        'variable %s must be configured in %s file.',
                        $envVarName,
                        $configurationFilePath
                    ));
                }
                $val = $this->trimComment($iniArray[$envVarName]);
                $lockFilePath = str_replace($varName, $val, $lockFilePath);
            }
        }
        $this->filePathServerLock = $lockFilePath;
    }

    /**
     * @param string $var
     *
     * @return string
     */
    private function trimComment($var)
    {
        return strstr($var, '#', true);
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        $interrupt = false;
        if ($this->isMaintenance()) {
            $context->getLogger()->notice('The maintenance mode has been activated.');
            $interrupt = true;
            do {
                $context->getLogger()->info('Waiting for the maintenance mode deactivation.');
                sleep($this->idleTime);
            } while ($this->isMaintenance());
        }

        if ($interrupt) {
            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('The Maintenance mode has been deactivated.');
        }
    }

    /**
     * Returns true if system in maintenance mode.
     *
     * @return bool
     */
    protected function isMaintenance()
    {
        $result = $this->maintenance->isOn();

        if (!$result && $this->filePathServerLock) {
            $result = $this->isMaintenanceServer();
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function isMaintenanceServer()
    {
        return file_exists($this->filePathServerLock);
    }
}
