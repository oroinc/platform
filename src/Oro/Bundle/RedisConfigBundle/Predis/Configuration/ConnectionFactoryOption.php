<?php

declare(strict_types=1);

namespace Oro\Bundle\RedisConfigBundle\Predis\Configuration;

use Oro\Bundle\RedisConfigBundle\Predis\Connection\StreamConnection;
use Predis\Configuration\ConnectionFactoryOption as BaseConnectionFactoryOption;
use Predis\Configuration\OptionsInterface;

/**
 * Creates the connections over TLS as {@see StreamConnection}, which does not change the default stream context.
 *
 * A connection class in the "connections" option of the client replaces this default.
 */
class ConnectionFactoryOption extends BaseConnectionFactoryOption
{
    public const array TLS_SCHEMES = ['tls', 'rediss'];

    #[\Override]
    public function getDefault(OptionsInterface $options)
    {
        $factory = parent::getDefault($options);
        foreach (self::TLS_SCHEMES as $scheme) {
            $factory->define($scheme, StreamConnection::class);
        }

        return $factory;
    }
}
