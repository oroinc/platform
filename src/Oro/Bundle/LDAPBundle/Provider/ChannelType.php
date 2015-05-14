<?php
namespace Oro\Bundle\LDAPBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;

class ChannelType implements ChannelInterface
{
    const TYPE = 'ldap';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.ldap.channel_type.label';
    }
}