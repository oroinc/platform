<?php
namespace Oro\Bundle\LDAPBundle\LDAP;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\LDAP\Factory\LdapManagerFactory;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\LDAPBundle\Model\User as LdapUser;

/**
 * Class LdapChannelManager
 *
 * Manages LDAP operations in context of integration channels.
 *
 * @package Oro\Bundle\LDAPBundle\LDAP
 */
class LdapChannelManager
{
    /** @var Channel[] */
    private $channels = [];

    /** @var Registry */
    private $registry;

    /** @var LdapManagerFactory */
    private $managerFactory;

    public function __construct(Registry $registry, LdapManagerFactory $managerFactory)
    {
        $this->registry = $registry;
        $this->managerFactory = $managerFactory;
    }

    /**
     * Returns all users from integration channel.
     *
     * @param Channel $channel
     *
     * @return array Array of users from LDAP server
     */
    public function findUsersThroughChannel(Channel $channel)
    {
        return $this->managerFactory->getInstanceForChannel($channel)->findUsers();
    }

    /**
     * Returns dn of a user or false if not found.
     *
     * @param Channel $channel
     * @param User $user
     * @return string|bool
     */
    protected function getUserDn(Channel $channel, User $user)
    {
        $mappings = (array)$user->getLdapMappings();

        return isset($mappings[$channel->getId()]) ? $mappings[$channel->getId()] : false;
    }

    /**
     * Sets and stores users Dn in database.
     *
     * @param Channel $channel
     * @param User $user
     * @param $dn
     */
    protected function storeUserDn(Channel $channel, User $user, $dn)
    {
        $this->setUserDn($channel, $user, $dn);

        $this->registry->getManager()->persist($user);
    }

    /**
     * Sets user Dn for provided channel.
     *
     * @param Channel $channel
     * @param User $user
     * @param $dn
     */
    protected function setUserDn(Channel $channel, User $user, $dn)
    {
        $user->setLdapMappings(((array)$user->getLdapMappings()) + [$channel->getId() => $dn]);
    }

    /**
     * Hydrates user with data from channel.
     *
     * @param Channel $channel
     * @param User $user
     * @param array $entry
     *
     * @return UserInterface
     */
    public function hydrateThroughChannel(Channel $channel, User $user, array $entry)
    {
        $manager = $this->managerFactory->getInstanceForChannel($channel);

        $result =  $manager->hydrate($user, $entry);

        $this->setUserDn($channel, $user, $entry['dn']);

        return $result;
    }

    /**
     * Returns all LDAP channels.
     *
     * @return Channel[]
     */
    protected function getAllChannels()
    {
        return $this->registry->getRepository('OroIntegrationBundle:Channel')->findBy(['type' => 'ldap']);
    }

    /**
     * Returns array of integration channels with provided ids.
     *
     * @param array $channelIds
     * @return Channel[]
     */
    public function getChannels(array $channelIds)
    {
        return $this->registry->getRepository('OroIntegrationBundle:Channel')->findBy(['id' => $channelIds]);
    }

    /**
     * Checks if changed fields from change set are synchronized through provided channel.
     * Returns true if at least one field is synchronized. False if none.
     *
     * @param Channel $channel
     * @param array $changeSet
     * @return bool
     */
    protected function checkValidChangeSet(Channel $channel, array $changeSet)
    {
        $fields = $this->managerFactory->getInstanceForChannel($channel)->getSynchronizedFields();
        $changeSet = array_keys($changeSet);

        return !empty(array_intersect(
            $fields,
            $changeSet
        ));
    }

    /**
     * Exports user through channel. Dn of that user entity is automatically updated.
     *
     * @param Channel $channel
     * @param User $user
     */
    public function exportThroughChannel(Channel $channel, User $user)
    {
        $manager = $this->managerFactory->getInstanceForChannel($channel);
        $dn = $this->getUserDn($channel, $user);

        $newDn = $manager->save($user, $dn);

        if ($dn != $newDn) {
            $this->storeUserDn($channel, $user, $newDn);
        }
    }

    /**
     * Exports user through channels he has mapped to it.
     *
     * @param User $user
     * @param array|null $changeSet If provided, checks if change set is valid for each channel.
     * Does not export if it is not.
     * @param bool $onlyEnabled Export only through enabled channels.
     */
    public function exportThroughUsersChannels(User $user, $changeSet = null, $onlyEnabled = true)
    {
        $mappings = (array)$user->getLdapMappings();

        $channels = $this->getChannels(array_keys($mappings));

        foreach ($channels as $channel) {
            if ($onlyEnabled && !$channel->isEnabled()) {
                continue;
            }

            if (($changeSet !== null) && !$this->checkValidChangeSet($channel, $changeSet)) {
                continue;
            }

            $this->exportThroughChannel($channel, $user);
        }
    }

    /**
     * Exports user through all channels.
     *
     * @param array|null $changeSet If provided, checks if change set is valid for each channel.
     * Does not export if it is not.
     * @param bool $onlyEnabled Export only through enabled channels.
     * @param bool $onlyNewEnabled Export only when option to export new users is enabled.
     */
    public function exportThroughAllChannels(User $user, $changeSet = null, $onlyEnabled = true, $onlyNewEnabled = true)
    {
        $channels = $this->getAllChannels();

        foreach ($channels as $channel) {
            if ($onlyEnabled && !$channel->isEnabled()) {
                continue;
            }

            if ($onlyNewEnabled && !$channel->getMappingSettings()->offsetGet('export_auto_enable')) {
                continue;
            }

            if (($changeSet !== null) && !$this->checkValidChangeSet($channel, $changeSet)) {
                continue;
            }

            $this->exportThroughChannel($channel, $user);
        }
    }

    /**
     * Checks if user exists in channel.
     *
     * @param Channel $channel
     * @param User $user
     * @return bool
     */
    public function existsInChannel(Channel $channel, User $user)
    {
        $manager = $this->managerFactory->getInstanceForChannel($channel);
        $dn = $this->getUserDn($channel, $user);

        return $manager->exists($user, $dn);
    }

    /**
     * Returns username attribute of provided channel.
     *
     * @param Channel $channel
     * @return string
     */
    public function getChannelUsernameAttr(Channel $channel)
    {
        return $this->managerFactory->getInstanceForChannel($channel)->getUsernameAttr();
    }

    /**
     * Checks credentials against users' channels.
     * Returns true if it is successful against at least one.
     *
     * @param UserInterface $user
     * @param $password
     * @param bool $onlyEnabled
     * @return bool|Channel false if failed, Channel if successful against it.
     */
    public function checkAuthAgainstUsersChannels(UserInterface $user, $password, $onlyEnabled = true)
    {
        $mappings = (array)$user->getLdapMappings();
        $channels = $this->getChannels(array_keys($mappings));

        foreach ($channels as $channel) {
            if ($onlyEnabled && !$channel->isEnabled()) {
                continue;
            }

            $manager = $this->managerFactory->getInstanceForChannel($channel);
            $ldapUser = LdapUser::createFromUser($user, $channel->getId());

            if ($manager->bind($ldapUser, $password)) {
                return $channel;
            }
        }

        return false;
    }
}