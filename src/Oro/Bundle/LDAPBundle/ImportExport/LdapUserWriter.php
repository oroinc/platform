<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\LDAP\LdapChannelManager;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LdapUserWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    use HasChannel;

    /** @var UserManager */
    private $userManager;

    /** @var ContextRegistry */
    private $contextRegistry;

    /** @var LdapChannelManager */
    private $channelManager;

    public function __construct(
        UserManager $userManager,
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $connectorContextMediator,
        LdapChannelManager $channelManager
    ) {
        $this->userManager = $userManager;
        $this->contextRegistry = $contextRegistry;
        $this->setConnectorContextMediator($connectorContextMediator);
        $this->channelManager = $channelManager;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $user) {
            if ($this->channelManager->existsInChannel($this->getChannel(), $user)) {
                $this->context->incrementUpdateCount();
            } else {
                $this->context->incrementAddCount();
            }

            $this->channelManager->exportThroughChannel($this->getChannel(), $user);
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
