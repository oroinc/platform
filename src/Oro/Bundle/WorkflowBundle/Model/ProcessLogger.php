<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

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
     * @param DoctrineHelper $doctrineHelper
     * @param LoggerInterface $logger
     */
    public function __construct(DoctrineHelper $doctrineHelper, LoggerInterface $logger = null)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
    }

    /**
     * @param string $message
     * @param ProcessTrigger $trigger
     * @param ProcessData $data
     */
    public function debug($message, ProcessTrigger $trigger, ProcessData $data)
    {
        if ($this->logger) {
            $context = array(
                'definition' => $trigger->getDefinition()->getName(),
                'event'      => $trigger->getEvent(),
                'entityId'   => $this->doctrineHelper->getSingleEntityIdentifier($data['data'], false),
            );
            $this->logger->debug($message, $context);
        }
    }
}
