<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_36;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesTopic;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;

class UpdateVisibilitiesMigrationQuery implements MigrationQuery
{
    private MessageProducerInterface $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return 'Schedule the update of visibilities for emails and email addresses.';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->producer->send(UpdateVisibilitiesTopic::getName(), []);
    }
}
