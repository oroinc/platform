<?php

namespace Oro\Bundle\RedisConfigBundle\Predis;

use Oro\Bundle\RedisConfigBundle\Predis\Configuration\IpAddressProvider;
use Oro\Bundle\RedisConfigBundle\Predis\Configuration\Options;
use Predis\Client as BaseClient;
use Predis\Configuration\OptionsInterface;

/**
 * Overrides predis client options and extends it with the IP address provider option
 */
class Client extends BaseClient
{
    /**
     * {@inheritDoc}
     */
    protected function createOptions($options)
    {
        if (is_array($options)) {
            $ipServerProvider = $options['_ipServerProvider'] ?? null;
            if (!$ipServerProvider instanceof IpAddressProvider) {
                throw  new \InvalidArgumentException("Server IP provider isn't set for predis client");
            }

            $options = new Options($options);
            $options->setIpAddressProvider($ipServerProvider);

            return $options;
        }

        if ($options instanceof OptionsInterface) {
            return $options;
        }

        throw new \InvalidArgumentException('Invalid type for client options.');
    }
}
