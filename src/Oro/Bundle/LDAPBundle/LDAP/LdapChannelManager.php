<?php
namespace Oro\Bundle\LDAPBundle\LDAP;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\LDAP\Factory\LdapManagerFactory;
use Oro\Bundle\UserBundle\Entity\User;

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
     * @param integer|Channel $channel
     *
     * @return array Array of users from LDAP server
     */
    public function findUsers($channel)
    {
        $channel = $this->getChannel($channel);

        return $this->managerFactory->getInstanceForChannel($channel)->findUsers();
    }

    /**
     * Saves user to channel
     *
     * @param user $user
     * @param Channel|integer|array $channels
     */
    public function save(User $user, $channels = null)
    {
        if ($channels === null) {
            $channels = array_keys((array)$user->getLdapMappings());
        }

        if (!is_array($channels)) {
            $channels = [$channels];
        }

        foreach ($channels as $channel) {
            $channel = $this->getChannel($channel);
            $this->saveToChannel($channel, $user);
        }
    }

    /**
     * Retrieves and returns channel if necessary.
     *
     * @param Channel|integer $channel
     * @return Channel
     */
    protected function getChannel($channel)
    {
        if ($channel instanceof Channel) {
            if (!isset($this->channels[$channel->getId()])) {
                $this->channels[$channel->getId()] = $channel;
            }

            return $channel;
        }

        if (isset($this->channels[$channel])) {
            return $this->channels[$channel];
        }

        $channelRepository = $this->registry->getRepository('OroIntegrationBundle:Channel');

        return $this->channels[$channel] = $channelRepository->find($channel);
    }

    /**
     * Saves a User to single, specified channel.
     * There may be changes to $user instance after this operation.
     * They should be persisted.
     *
     * @param Channel $channel
     * @param User $user
     */
    protected function saveToChannel(Channel $channel, User $user)
    {
        $manager = $this->managerFactory->getInstanceForChannel($channel);
        $dn = $this->getUserDn($channel, $user);

        $newDn = $manager->save($user, $dn);

        if ($dn != $newDn) {
            $this->setUserDn($channel, $user, $newDn);
        }
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
    public function setUserDn(Channel $channel, User $user, $dn)
    {
        $user->setLdapMappings(((array)$user->getLdapMappings()) + [$channel->getId() => $dn]);
    }

    /**
     * Checks if user has mapping in provided channel and if it exists in LDAP.
     *
     * @param Channel|integer $channel
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public function exists($channel, User $user)
    {
        $channel = $this->getChannel($channel);
        $manager = $this->managerFactory->getInstanceForChannel($channel);
        $dn = $this->getUserDn($channel, $user);

        return $manager->exists($user, $dn);
    }

    /**
     * Hydrates user with data from channel.
     *
     * @param Channel|integer $channel
     * @param User $user
     * @param array $entry
     *
     * @return UserInterface
     */
    public function hydrate($channel, User $user, array $entry)
    {
        $manager = $this->managerFactory->getInstanceForChannel($channel);

        return $manager->hydrate($user, $entry);
    }

    /**
     * Checks credentials against LDAP Channel(s).
     *
     * @param Channel|integer|array $channels
     * @param User $user
     * @param $password
     *
     * @return boolean
     */
    public function bind(User $user, $password, $channels = null)
    {
        if ($channels === null) {
            $channels = array_keys((array)$user->getLdapMappings());
        }

        if (!is_array($channels)) {
            $channels = [$channels];
        }

        foreach ($channels as $channel) {
            $channel = $this->getChannel($channel);
            if ($this->bindToChannel($channel, $user, $password)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks credentials against LDAP Channel.
     *
     * @param Channel|integer $channel
     * @param User $user
     * @param $password
     *
     * @return boolean
     */
    protected function bindToChannel(Channel $channel, User $user, $password)
    {
        $mappings = (array)$user->getLdapMappings();

        // If user has no mapping in channel
        if (false === array_search($channel->getId(), array_keys($mappings))) {
            return false;
        }

        // If integration is disabled, return false.
        if (!$channel->isEnabled()) {
            return false;
        }

        // Continue with binding to single channel.
        $manager = $this->managerFactory->getInstanceForChannel($channel);

        return $manager->bind($user, $password);
    }

    /**
     * @param Channel|integer $channel
     * @return LdapManager
     * @throws \Exception
     */
    public function getLdapManager($channel)
    {
        $channel = $this->getChannel($channel);

        return $this->managerFactory->getInstanceForChannel($channel);
    }
}