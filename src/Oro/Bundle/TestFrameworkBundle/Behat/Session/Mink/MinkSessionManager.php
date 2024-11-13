<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink;

use Behat\Mink\Session;
use FriendsOfBehat\SymfonyExtension\Mink\Mink;

/**
 * Behat mink session manager class.
 */
class MinkSessionManager extends Mink
{
    protected ?WatchModeSessionHolder $sessionHolder;


    public function setSessionHolder(WatchModeSessionHolder $sessionHolder): void
    {
        $this->sessionHolder = $sessionHolder;
    }

    public function setDefaultSessionName($name): void
    {
        if ($this->sessionHolder->isWatchFrom() && $name !== $this->sessionHolder->getDefaultSessionName()) {
            parent::setDefaultSessionName($this->sessionHolder->getDefaultSessionName());
        } else {
            parent::setDefaultSessionName($name);
        }
        if ($this->sessionHolder->isWatchMode()) {
            $this->sessionHolder->setDefaultSessionName($name);
        }
    }

    public function getSession($name = null): Session
    {
        $session = parent::getSession($name);
        $name = $name ?? $this->getDefaultSessionName();
        if (null !== $session->getDriver()->getWebDriverSession()
            && $this->sessionHolder->isWatchMode()
            && !$this->sessionHolder->hasSession($name)) {
            $this->sessionHolder->register($name, $session->getDriver()->getWebDriverSession()->getUrl());
        }

        return $session;
    }

    public function __destruct()
    {
        if (!$this->sessionHolder->isWatchFrom()) {
            $this->stopSessions();
        }
    }
}
