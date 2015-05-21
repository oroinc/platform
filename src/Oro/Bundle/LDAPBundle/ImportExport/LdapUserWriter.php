<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\LDAP\Factory\LdapManagerFactory;
use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LdapUserWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    /** @var LdapManagerFactory */
    protected $ldapManagerFactory;

    /** @var UserManager */
    protected $userManager;

    /** @var ContextRegistry */
    protected $contextRegistry;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var ContextInterface */
    protected $context;

    /** @var LdapManager */
    protected $ldapManager;

    /** @var ConnectorContextMediator */
    private $connectorContextMediator;

    public function __construct(
        LdapManagerFactory $ldapManagerFactory,
        UserManager $userManager,
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $connectorContextMediator
    ) {
        $this->ldapManagerFactory = $ldapManagerFactory;
        $this->userManager = $userManager;
        $this->contextRegistry = $contextRegistry;
        $this->connectorContextMediator = $connectorContextMediator;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {

        foreach ($items as $user) {
            if ($this->getLdapManager()->exists($user)) {
                $this->context->incrementUpdateCount();
            } else {
                $this->context->incrementAddCount();
            }

            $this->getLdapManager()->save($user);
        }

        $this->userManager->getStorageManager()->flush();
    }

    /**
     * Returns LdapManager of this channel.
     *
     * @return LdapManager
     *
     * @throws \Exception
     */
    private function getLdapManager()
    {
        return $this->ldapManager === null ? $this->ldapManager = $this->ldapManagerFactory->getInstanceForChannel(
            $this->connectorContextMediator->getChannel($this->context)
        ) : $this->ldapManager;
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        $this->context = $this->contextRegistry->getByStepExecution($this->stepExecution);
    }
}
