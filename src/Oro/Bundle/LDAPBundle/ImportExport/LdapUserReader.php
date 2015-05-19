<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;
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
        // If users are not loaded yet ...
        if (null === $this->users) {
            // Get array of users ...
            $users = $this->ldapManager->findUsers();
            unset($users['count']);

            // Create iterator
            $this->users = new \ArrayIterator($users);
        }

        // If there are no more valid users ...
        if (!$this->users->valid()) {
            return null;
        }

        $user = $this->users->current();
        $this->users->next();
        $this->getContext()->incrementReadCount();

        return $user;
    }
}