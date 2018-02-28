<?php

namespace Oro\Bundle\BatchBundle\Monolog\Handler;

use Akeneo\Bundle\BatchBundle\Monolog\Handler\BatchLogHandler as AkeneoBatchLogHandler;
use Monolog\Logger;

/**
 * Write the log into a separate log file
 */
class BatchLogHandler extends AkeneoBatchLogHandler
{
    /** @var bool */
    protected $isActive = false;

    /**
     * {@inheritDoc}
     *
     * todo: Remove after update AkeneoBatchBundle to version without call of Monolog\Handler\StreamHandler constructor
     */
    public function __construct($logDir)
    {
        $this->logDir = $logDir;

        $this->filePermission = null;
        $this->useLocking = false;

        $this->setLevel(Logger::DEBUG);
        $this->bubble = true;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $record)
    {
        if ($this->isActive()) {
            parent::write($record);
        }
    }
}
