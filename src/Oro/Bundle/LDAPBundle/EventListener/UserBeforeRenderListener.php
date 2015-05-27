<?php
namespace Oro\Bundle\LDAPBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\LDAP\LdapChannelManager;
use Oro\Bundle\UserBundle\Entity\User;

class UserBeforeRenderListener
{
    /**
     * @var LdapChannelManager
     */
    private $channelManager;

    public function __construct(LdapChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * Handles transformation of ldap mappings before rendering.
     *
     * @param ValueRenderEvent $event
     */
    public function beforeValueRender(ValueRenderEvent $event)
    {
        if (($event->getEntity() instanceof User) && $event->getFieldConfigId()->getFieldName() == 'ldap_mappings') {
            $value = (array) $event->getFieldValue();

            $mappings = [];

            /** @var Channel[] $channels */
            $channels = $this->channelManager->getChannels(array_keys($value));
            foreach ($channels as $channel) {
                $mappings[$channel->getName()] = $value[$channel->getId()];
            }

            $event->setFieldViewValue([
                'mappings' => $mappings,
                'template' => 'OroLDAPBundle:User:ldapMappings.html.twig',
            ]);
        }
    }
}