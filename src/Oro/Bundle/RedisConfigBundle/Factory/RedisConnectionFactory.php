<?php

namespace Oro\Bundle\RedisConfigBundle\Factory;

use Oro\Bundle\RedisConfigBundle\Predis\Client;
use Oro\Bundle\RedisConfigBundle\Predis\Configuration\IpAddressProvider;
use Oro\Component\PhpUtils\DsnUtil;
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

    public function __invoke(#[\SensitiveParameter] string $dsn): mixed
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
        $sslParameters = $this->getSslParameters($dsn);
        if (str_starts_with($dsn, 'rediss:') || str_starts_with($dsn, 'valkeys:')) {
            // Predis creates the connections to the nodes that a sentinel returns from the default parameters.
            // Thus these parameters carry the TLS scheme and the ssl options of the DSN.
            $options['parameters'] = ['scheme' => 'tls'] + $sslParameters;
        }

        try {
            return RedisAdapter::createConnection($dsn, $options);
        } catch (InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'does not start with "redis[s]:"')) {
                return null;
            }

            throw $e;
        }
    }

    /**
     * Reads the ssl[...] options of the DSN query as RedisTrait::createConnection() of Symfony reads them.
     * Symfony converts an unknown boolean value to false, which turns the verification off.
     * Thus this method rejects such a value first.
     */
    private function getSslParameters(#[\SensitiveParameter] string $dsn): array
    {
        // The credentials can contain characters that break parse_url(), so they are removed first.
        $dsnWithoutCredentials = preg_replace('#^[a-z]+:(//)?(?:(?:[^:@]*+:)?[^@]*+@)?#', 'file:$1', $dsn);
        parse_str((string) parse_url($dsnWithoutCredentials, PHP_URL_QUERY), $query);
        // Symfony reads an ssl parameter without named options, for example ssl=1, with a warning and ignores it.
        // The factory does not reject it, so a DSN that works before the upgrade continues to work.
        if (!isset($query['ssl']) || !\is_array($query['ssl'])) {
            return [];
        }

        return ['ssl' => DsnUtil::getSslContextOptions($query['ssl'], 'Redis DSN')];
    }
}
