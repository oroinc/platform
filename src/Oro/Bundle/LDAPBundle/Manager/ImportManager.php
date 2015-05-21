<?php

namespace Oro\Bundle\LDAPBundle\Manager;

use Exception;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use FR3D\LdapBundle\Driver\LdapDriverException;

use Psr\Log\LoggerInterface;

use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\LDAPBundle\Provider\UserProvider;
use Oro\Bundle\UserBundle\Entity\UserManager;

class ImportManager
{
    /** @var LdapManager */
    protected $ldapManager;

    /** @var UserManager */
    protected $userManager;

    /** @var Registry */
    protected $registry;

    /** @var UserProvider */
    protected $userProvider;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LdapManager $ldapManager
     * @param UserManager $userManager
     * @param Registry $registry
     * @param UserProvider $userProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        LdapManager $ldapManager,
        UserManager $userManager,
        Registry $registry,
        UserProvider $userProvider,
        LoggerInterface $logger
    ) {
        $this->ldapManager = $ldapManager;
        $this->userManager = $userManager;
        $this->registry = $registry;
        $this->userProvider = $userProvider;
        $this->logger = $logger;
    }

    /**
     * @param bool $dryRun
     *
     * @return array Result
     */
    public function import($dryRun)
    {
        $result = [
            'add'     => 0,
            'replace' => 0,
            'total'   => 0,
            'errors'  => [],
        ];

        try {
            $ldapResults = $this->ldapManager->findUsers();
            $ldapUsers = $this->extractLdapUsers($ldapResults);

            if (!$dryRun) {
                $this->importUsers($ldapUsers);
            }

            $ldapUsernames = array_keys($ldapUsers);
            $usersCount = $this->userProvider->getNumberOfUsersByUsernames($ldapUsernames);

            $result['add']     = $ldapResults['count'] - $usersCount;
            $result['replace'] = $usersCount;
            $result['total']   = $ldapResults['count'];
        } catch (LdapDriverException $ex) {
            $result['errors'][] = 'oro.ldap.import_users.search_error';
            $this->logger->error($ex->getMessage(), ['exception' => $ex]);
        } catch (Exception $ex) {
            $result['errors'][] = 'oro.ldap.import_users.error';
            $this->logger->error($ex->getMessage(), ['exception' => $ex]);
        }

        $result['done'] = !$dryRun && !$result['errors'];

        return $result;
    }

    /**
     * Imports users
     */
    protected function importUsers(array $ldapUsers)
    {
        $em = $this->getUserEntityManager();
        $ldapUsernames = array_keys($ldapUsers);

        $users = $this->userProvider->findUsersByUsernames($ldapUsernames);
        foreach ($users as $user) {
            $entry = $ldapUsers[$user->getUsername()];
            $this->ldapManager->hydrate($user, $entry);
            unset($ldapUsers[$user->getUsername()]);
        }

        foreach ($ldapUsers as $entry) {
            $user = $this->userManager->createUser();
            $this->ldapManager->hydrate($user, $entry);
            if (!$user->getPassword()) {
                $user->setPassword('');
            }
            $em->persist($user);
        }

        $em->flush();
    }

    /**
     * @param array $ldapResults
     *
     * @return array
     */
    protected function extractLdapUsers(array $ldapResults)
    {
        $ldapUsers = [];
        $usernameAttr = $this->ldapManager->getUsernameAttr();
        for ($i = 0; $i < count($ldapResults); $i++) {
            $ldapUsers[$ldapResults[$i][$usernameAttr][0]] = $ldapResults[$i];
        }

        return $ldapUsers;
    }

    /**
     * @return EntityManager
     */
    protected function getUserEntityManager()
    {
        return $this->registry->getManagerForClass('OroUserBundle:User');
    }
}
