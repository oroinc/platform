<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Psr\Log\LoggerInterface;

class ProcessLogger
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param LoggerInterface $logger
     */
    public function __construct(DoctrineHelper $doctrineHelper, LoggerInterface $logger = null)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param string $message
     * @param ProcessTrigger $trigger
     * @param ProcessData $data
     */
    public function debug($message, ProcessTrigger $trigger, ProcessData $data)
    {
        if (!$this->enabled) {
            return;
        }
        if ($this->logger) {
            $context = ['definition' => $trigger->getDefinition()->getName()];
            if ($trigger->getEvent()) {
                $context['event'] = $trigger->getEvent();
            }
            if ($trigger->getCron()) {
                $context['cron'] = $trigger->getCron();
            }
            if ($data['data']) {
                $context['entityId'] = $this->doctrineHelper->getSingleEntityIdentifier($data['data'], false);
            }
            $this->logger->debug($message, $context);
        }
    }
}
