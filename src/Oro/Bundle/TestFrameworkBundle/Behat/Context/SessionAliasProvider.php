<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\Mink\Mink;
use Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink\WatchModeSessionHolder;

/**
 * Provides possibility to work with multiple sessions in one behat feature.
 */
class SessionAliasProvider implements MultiSessionAwareInterface
{
    private array $aliases = [];
    private array $data = [];

    public function __construct(private readonly WatchModeSessionHolder $sessionHolder)
    {
    }

    public function setSessionAlias(Mink $mink, string $sessionName, string $alias): void
    {
        if (!$mink->hasSession($sessionName)) {
            throw new \RuntimeException(
                sprintf(
                    'Can not register alias `%s` for session `%s` as the session does not exists',
                    $alias,
                    $sessionName
                )
            );
        }
        $this->sessionHolder->setSessionAlias($alias, $sessionName);
        $this->aliases[$alias] = $sessionName;
    }

    public function switchSessionByAlias(Mink $mink, string $alias): void
    {
        if ($this->hasRegisteredAlias($alias)) {
            $sessionName = $this->getSessionName($alias);

            $this->switchSession($mink, $sessionName);
        } else {
            throw new \RuntimeException(sprintf('Alias `%s` for session is not defined', $alias));
        }
    }

    public function switchSession(Mink $mink, string $sessionName): void
    {
        $this->sessionHolder->setDefaultSessionName($sessionName);
        $mink->setDefaultSessionName($sessionName);

        $session = $mink->getSession($sessionName);

        // start session if needed
        if (!$session->isStarted()) {
            $session->start();
        }

        $session->switchToWindow(0);
    }

    /**
     * @param string $alias
     * @return mixed
     * @throws \OutOfBoundsException
     */
    #[\Override]
    public function getSessionName($alias): string
    {
        if (($this->sessionHolder->isWatchMode() || $this->sessionHolder->isWatchFrom())
            && $this->sessionHolder->hasSessionAlias($alias))
        {
            return $this->sessionHolder->getSessionNameByAlias($alias);
        }
        if (isset($this->aliases[$alias])) {
            return $this->aliases[$alias];
        }

        throw new \OutOfBoundsException(
            sprintf('Unknown session alias `%s`', $alias)
        );
    }

    /**
     * @param string $alias
     */
    #[\Override]
    public function hasRegisteredAlias($alias): bool
    {
        return isset($this->aliases[$alias]) || $this->sessionHolder->hasSessionAlias($alias);
    }

    #[\Override]
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @param string $sessionAlias
     * @param string $key
     * @param mixed $value
     */
    #[\Override]
    public function saveSessionValue($sessionAlias, $key, $value)
    {
        $sessionName = $this->getSessionName($sessionAlias);

        if (!isset($this->data[$sessionName])) {
            $this->data[$sessionName] = [];
        }

        $this->data[$sessionName][$key] = $value;
    }

    /**
     * @param string $sessionAlias
     * @param string $key
     * @param null|mixed $default
     * @return mixed
     */
    #[\Override]
    public function getSessionValue($sessionAlias, $key, $default = null)
    {
        $sessionName = $this->getSessionName($sessionAlias);

        if (isset($this->data[$sessionName][$key])) {
            return $this->data[$sessionName][$key];
        }

        return $default;
    }
}
