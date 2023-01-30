<?php

namespace Oro\Bundle\SecurityBundle\Session;

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Factory to create targeted session handler instance(native(file), pdo, redis).
 */
class SessionHandlerFactory
{
    public static function create(
        ServiceLocator $locator,
        string $dsn
    ): \SessionHandlerInterface {
        return $locator->get(self::getSessionHandlerAlias($dsn));
    }

    private static function getSessionHandlerAlias(string $dsn): string
    {
        preg_match('#^(\w+):#', $dsn, $matches);
        $scheme = $matches[1] ?? null;

        if (empty($scheme)) {
            throw new \InvalidArgumentException(sprintf(
                'The "%s" session handler DSN must contain a scheme.',
                $dsn
            ));
        }

        return $scheme;
    }
}
