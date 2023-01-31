<?php

namespace Oro\Bundle\RedisConfigBundle\Factory;

use Oro\Bundle\RedisConfigBundle\Predis\Client;
use Oro\Bundle\RedisConfigBundle\Predis\Configuration\IpAddressProvider;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * Redis connection factory that utilizes customized both 'predis' client and options components.
 */
class RedisConnectionFactory
{
    private IpAddressProvider $ipAddressProvider;

    public function __construct(IpAddressProvider $ipAddressProvider)
    {
        $this->ipAddressProvider = $ipAddressProvider;
    }

    public function __invoke(string $dsn): mixed
    {
        $options = [
            'lazy' => true,
            'class' => Client::class,
            '_ipServerProvider' => $this->ipAddressProvider,
        ];
        if (str_starts_with($dsn, 'redis:///')) {
            // connection by socket requires disabling of tcp_nodelay parameter
            $options += [
                'parameters' => [
                    'tcp_nodelay' => null,
                ],
            ];
        }
        if (preg_match("/(&|:\?)cluster=/", $dsn)) {
            $options['failover'] = 'slaves';
        }

        try {
            return RedisAdapter::createConnection($dsn, $options);
        } catch (InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'does not start with "redis:" or "rediss"')) {
                return null;
            }

            throw $e;
        }
    }
}
