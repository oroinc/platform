<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\Mink\Mink;

class SessionAliasProvider implements MultiSessionAwareInterface
{
    /**
     * @var array|string[]
     */
    private $aliases = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @param Mink $mink
     * @param string $sessionName
     * @param string $alias
     */
    public function setSessionAlias(Mink $mink, $sessionName, $alias)
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
        $this->aliases[$alias] = $sessionName;
    }

    /**
     * @param Mink $mink
     * @param string $alias
     */
    public function switchSessionByAlias(Mink $mink, $alias)
    {
        if ($this->hasRegisteredAlias($alias)) {
            $sessionName = $this->getSessionName($alias);

            $this->switchSession($mink, $sessionName);
        } else {
            throw new \RuntimeException(
                sprintf('Alias `%s` for session is not defined', $alias)
            );
        }
    }

    /**
     * @param Mink $mink
     * @param string $sessionName
     */
    public function switchSession(Mink $mink, $sessionName)
    {
        $mink->setDefaultSessionName($sessionName);
        $mink->getSession($sessionName)->switchToWindow(0);
    }

    /**
     * @param string $alias
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function getSessionName($alias)
    {
        if (isset($this->aliases[$alias])) {
            return $this->aliases[$alias];
        }

        throw new \OutOfBoundsException(
            sprintf('Unknown session alias `%s`', $alias)
        );
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function hasRegisteredAlias($alias)
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * @param string $sessionAlias
     * @param string $key
     * @param mixed $value
     */
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
    public function getSessionValue($sessionAlias, $key, $default = null)
    {
        $sessionName = $this->getSessionName($sessionAlias);

        if (isset($this->data[$sessionName][$key])) {
            return $this->data[$sessionName][$key];
        }

        return $default;
    }
}
