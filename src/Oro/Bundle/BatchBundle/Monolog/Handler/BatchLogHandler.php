<?php

namespace Oro\Bundle\BatchBundle\Monolog\Handler;

use Akeneo\Bundle\BatchBundle\Monolog\Handler\BatchLogHandler as AkeneoBatchLogHandler;

/**
 * Write the log into a separate log file
 */
class BatchLogHandler extends AkeneoBatchLogHandler
{
    /** @var bool */
    protected $isActive = false;

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
