<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LdapUserWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    /** @var LdapManager */
    protected $ldapManager;

    /** @var UserManager */
    protected $userManager;

    /** @var ContextRegistry */
    protected $contextRegistry;

    /** @var StepExecution */
    protected $stepExecution;

    /**
     * @param LdapManager $ldapManager
     * @param UserManager $userManager
     * @param ContextRegistry $contextRegistry
     */
    public function __construct(LdapManager $ldapManager, UserManager $userManager, ContextRegistry $contextRegistry)
    {
        $this->ldapManager = $ldapManager;
        $this->userManager = $userManager;
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $context = $this->contextRegistry->getByStepExecution($this->stepExecution);

        foreach ($items as $user) {
            if ($this->ldapManager->exists($user)) {
                $context->incrementUpdateCount();
            } else {
                $context->incrementAddCount();
            }

            $this->ldapManager->save($user);
        }

        $this->userManager->getStorageManager()->flush();
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }
}
