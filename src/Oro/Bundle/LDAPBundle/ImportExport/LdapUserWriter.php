<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LdapUserWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    use HasChannel;

    /** @var UserManager */
    private $userManager;

    /** @var ContextRegistry */
    private $contextRegistry;

    /** @var ChannelManagerProvider */
    private $managerProvider;

    public function __construct(
        UserManager $userManager,
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $connectorContextMediator,
        ChannelManagerProvider $managerProvider
    ) {
        $this->userManager = $userManager;
        $this->contextRegistry = $contextRegistry;
        $this->setConnectorContextMediator($connectorContextMediator);
        $this->managerProvider = $managerProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $user) {
            if ($this->managerProvider->channel($this->getChannel())->exists($user)) {
                $this->context->incrementUpdateCount();
            } else {
                $this->context->incrementAddCount();
            }

            $this->managerProvider->channel($this->getChannel())->save($user);
        }

        $this->userManager->getStorageManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->setContext($this->contextRegistry->getByStepExecution($stepExecution));
    }
}
