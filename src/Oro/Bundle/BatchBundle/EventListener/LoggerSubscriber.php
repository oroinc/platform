<?php

namespace Oro\Bundle\BatchBundle\EventListener;

use Akeneo\Bundle\BatchBundle\Event\InvalidItemEvent;
use Akeneo\Bundle\BatchBundle\Event\JobExecutionEvent;
use Akeneo\Bundle\BatchBundle\Event\StepExecutionEvent;
use Akeneo\Bundle\BatchBundle\EventListener\LoggerSubscriber as AkeneoLoggerSubscriber;

/**
 * Subscriber to log job execution result
 * Acts as a wrapper for Akeneo LoggerSubscriber to enable/disable log writing
 */
class LoggerSubscriber extends AkeneoLoggerSubscriber
{

    /** @var bool */
    protected $isActive = true;

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
    public function beforeJobExecution(JobExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::beforeJobExecution($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jobExecutionStopped(JobExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::jobExecutionStopped($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jobExecutionInterrupted(JobExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::jobExecutionInterrupted($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jobExecutionFatalError(JobExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::jobExecutionFatalError($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeJobStatusUpgrade(JobExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::beforeJobStatusUpgrade($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeStepExecution(StepExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::beforeStepExecution($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stepExecutionSucceeded(StepExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::stepExecutionSucceeded($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stepExecutionInterrupted(StepExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::stepExecutionInterrupted($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stepExecutionErrored(StepExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::stepExecutionErrored($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stepExecutionCompleted(StepExecutionEvent $event)
    {
        if ($this->isActive()) {
            parent::stepExecutionCompleted($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function invalidItem(InvalidItemEvent $event)
    {
        if ($this->isActive()) {
            parent::invalidItem($event);
        }
    }
}
