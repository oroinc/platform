<?php
/**
 * ChannelType.php
 *
 * Project: crm-enterprise-dev
 * Author: Jakub Babiak <jakub@babiak.cz>
 * Created: 13/05/15 09:46
 */

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