<?php

namespace Oro\Bundle\LDAPBundle\Manager;

use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\UserBundle\Entity\UserManager;

class ExportManager
{
    /** @var LdapManager */
    protected $ldapManager;

    /** @var UserManager */
    protected $userManager;

    /**
     * @param LdapManager $ldapManager
     * @param UserManager $userManager
     */
    public function __construct(
        LdapManager $ldapManager,
        UserManager $userManager
    ) {
        $this->ldapManager = $ldapManager;
        $this->userManager = $userManager;
    }

    public function export($dryRun)
    {
        $users = $this->userManager->findUsers();

        $result = [
            'add'     => 0,
            'replace' => 0,
            'total'   => count($users),
            'errors'  => [],
        ];

        try {
            foreach ($users as $user) {
                if ($this->ldapManager->exists($user)) {
                    $result['replace'] += 1;
                } else {
                    $result['add'] += 1;
                }

                if (!$dryRun) {
                    $this->ldapManager->save($user);
                }
            }
        } catch (Exception $ex) {
            $result['errors'][] = 'oro.ldap.export_users.error';
        }

        $result['done'] = !$dryRun && !$result['errors'];

        return $result;
    }
}
