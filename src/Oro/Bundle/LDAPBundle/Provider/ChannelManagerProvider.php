<?php

namespace Oro\Bundle\LDAPBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\LDAP\Ldap;
use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\LDAPBundle\LDAP\ZendLdapDriver;

class ChannelManagerProvider
{
    /**
     * Cashed LdapManager instances.
     *
     * @var LdapManager[]
     */
    protected $managers = [];

    /** @var Registry */
    private $registry;

    private $userManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var Channel[] */
    private $channels;

    public function __construct(Registry $registry, $userManager, LoggerInterface $logger = null)
    {
        $this->registry = $registry;
        $this->userManager = $userManager;
        $this->logger = $logger;
    }

    /**
     * Creates new
     *
     * @param Channel $channel
     *
     * @return LdapManager
     */
    protected function make(Channel $channel)
    {
        $ldap = new Ldap($channel);
        $driver = new ZendLdapDriver($ldap, $this->logger);
        return new LdapManager(
            $this->registry,
            $driver,
            $this->userManager,
            $channel
        );
    }

    /**
     * Returns instance of LdapManager configured using settings from Channel.
     *
     * @param Channel $channel
     *
     * @return LdapManager|null Returns null if channel is not of LDAP type.
     */
    public function channel(Channel $channel)
    {
        if (isset($this->managers[$channel->getId()])) {
            return $this->managers[$channel->getId()];
        }

        if ($channel->getType() != ChannelType::TYPE) {
            return null;
        }

        return $this->managers[$channel->getId()] = $this->make($channel);
    }

    /**
     * Returns all LDAP integration channels in system.
     *
     * @return Channel[]
     */
    public function getChannels()
    {
        if ($this->channels === null) {
            $channels =
                $this->registry->getRepository('OroIntegrationBundle:Channel')
                    ->findBy(['type' => ChannelType::TYPE ]);
            $this->channels = [];
            foreach ($channels as $channel) {
                $this->channels[$channel->getId()] = $channel;
            }
        }

        return $this->channels;
    }

    /**
     * Saves user through all available LDAP integration channels.
     *
     * @param UserInterface $user
     * @param bool $enabledOnly Ignore disabled integration channels.
     * @param bool $exportNewEnabledOnly Ignore channels without option to export new users enabled.
     */
    public function save(UserInterface $user, $enabledOnly = true, $exportNewEnabledOnly = true)
    {
        foreach ($this->getChannels() as $channel) {
            if ($enabledOnly && !$channel->isEnabled()) {
                continue;
            }

            if ($exportNewEnabledOnly && !$channel->getMappingSettings()->offsetGet('export_auto_enable')) {
                continue;
            }

            $this->channel($channel)->save($user);
        }
    }

    /**
     * Binds user to LDAP through first available enabled LDAP integration channel.
     *
     * @param UserInterface $user
     * @param $password
     * @return bool True if bound successfully.
     */
    public function bind(UserInterface $user, $password)
    {
        foreach ($this->getChannels() as $channel) {
            if (!$channel->isEnabled()) {
                continue;
            }

            if ($this->channel($channel)->bind($user, $password)) {
                return true;
            }
        }

        return false;
    }
}
