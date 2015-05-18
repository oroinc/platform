<?php

namespace Oro\Bundle\LDAPBundle\Manager;

use Psr\Log\LoggerInterface;

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

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LdapManager $ldapManager
     * @param UserManager $userManager
     * @param UserProvider $userProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        LdapManager $ldapManager,
        UserManager $userManager,
        UserProvider $userProvider,
        LoggerInterface $logger
    ) {
        $this->ldapManager = $ldapManager;
        $this->userManager = $userManager;
        $this->userProvider = $userProvider;
        $this->logger = $logger;
    }

    /**
     * @param bool $dryRun
     *
     * @return array Result
     */
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
                    $this->userManager->getStorageManager()->flush();
                    $this->userManager->getStorageManager()->clear();
                }
                $i++;
            }

            $this->userManager->getStorageManager()->flush();
            $this->userManager->getStorageManager()->clear();
        } catch (Exception $ex) {
            $result['errors'][] = 'oro.ldap.export_users.error';
             $this->logger->error($ex->getMessage(), ['exception' => $ex]);
        }

        $result['done'] = !$dryRun && !$result['errors'];

        return $result;
    }
}
