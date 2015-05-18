<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;
use Oro\Bundle\ImportExportBundle\Reader\ReaderInterface;
use Oro\Bundle\LDAPBundle\LDAP\LdapManager;

class LdapUserReader extends AbstractReader
{

    /** @var LdapManager  */
    protected $ldapManager;

    /** @var \Iterator */
    protected $users;

    public function __construct(ContextRegistry $contextRegistry, LdapManager $manager)
    {
        parent::__construct($contextRegistry);
        $this->ldapManager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (null === $this->users) {
            $this->users = new \ArrayIterator($this->ldapManager->findUsers());
        }
        if (!$this->users->valid()) {
            return null;
        }
        $user = $this->users->current();
        $this->users->next();
        return $user;
    }

    public function initializeFromContext(ContextInterface $context)
    {

    }
}