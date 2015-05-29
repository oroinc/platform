<?php
namespace Oro\Bundle\LDAPBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;
use Oro\Bundle\UserBundle\Entity\User;

class UserBeforeRenderListener
{
    private $managerProvider;

    public function __construct(ChannelManagerProvider $managerProvider)
    {
        $this->managerProvider = $managerProvider;
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
            $channels = $this->managerProvider->getChannels();
            foreach ($value as $channelId => $dn) {
                if (!isset($channels[$channelId])) {
                    continue;
                }
                $mappings[] = [
                    'name' => $channels[$channelId]->getName(),
                    'dn' => $dn,
                ];
            }

            $event->setFieldViewValue([
                'mappings' => $mappings,
                'template' => 'OroLDAPBundle:User:ldapMappings.html.twig',
            ]);
        }
    }
}
