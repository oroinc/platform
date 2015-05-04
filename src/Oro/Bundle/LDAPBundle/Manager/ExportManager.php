<?php

namespace Oro\Bundle\LDAPBundle\Manager;

use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\LDAPBundle\Provider\UserProvider;
use Oro\Bundle\UserBundle\Entity\UserManager;

class ExportManager
{
    const BATCH_SIZE = 100;

    /** @var LdapManager */
    protected $ldapManager;

    /** @var UserManager */
    protected $userManager;

    /** @var UserProvider */
    protected $userProvider;

    /**
     * @param LdapManager $ldapManager
     * @param UserManager $userManager
     * @param UserProvider $userProvider
     */
    public function __construct(
        LdapManager $ldapManager,
        UserManager $userManager,
        UserProvider $userProvider
    ) {
        $this->ldapManager = $ldapManager;
        $this->userManager = $userManager;
        $this->userProvider = $userProvider;
    }

    public function export($dryRun)
    {
        $users = $this->userProvider->getUsersIterator();

        $result = [
            'add'     => 0,
            'replace' => 0,
            'total'   => $this->userProvider->getNumberOfUsers(),
            'errors'  => [],
        ];

        try {
            $i = 0;
            foreach ($users as $row) {
                $user = $row[0];
                if ($this->ldapManager->exists($user)) {
                    $result['replace'] += 1;
                } else {
                    $result['add'] += 1;
                }

                if (!$dryRun) {
                    $this->ldapManager->save($user);
                }

                if ($i % static::BATCH_SIZE === 0) {
                    $this->userManager->getStorageManager()->clear();
                }
                $i++;
            }
        } catch (Exception $ex) {
            $result['errors'][] = 'oro.ldap.export_users.error';
        }

        $result['done'] = !$dryRun && !$result['errors'];

        return $result;
    }
}
