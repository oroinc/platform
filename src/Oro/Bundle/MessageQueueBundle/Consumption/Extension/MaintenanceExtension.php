<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Bundle\PlatformBundle\Maintenance\Mode;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class MaintenanceExtension extends AbstractExtension
{
    /**
     * @var Mode
     */
    private $maintenance;

    /**
     * @var string
     */
    private $filePathServerLock;

    /**
     * @var integer (seconds)
     */
    private $idleTime;

    /**
     * @param Mode $maintenance
     * @param integer $idleTime
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
                if (! isset($iniArray[$envVarName])) {
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
        while ($this->isMaintenance()) {
            $context->getLogger()->debug(
                '[MaintenanceExtension] Maintenance mode has been activated.',
                ['context' => $context]
            );
            $interrupt = true;
            sleep($this->idleTime);
        }

        if ($interrupt) {
            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('Maintenance mode has been deactivated.');
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
