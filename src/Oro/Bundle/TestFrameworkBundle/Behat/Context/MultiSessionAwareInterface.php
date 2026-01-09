<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

/**
 * Defines the contract for managing multiple browser sessions with named aliases.
 *
 * Classes implementing this interface can work with multiple concurrent browser sessions,
 * each identified by a unique alias, and can store and retrieve session-specific values.
 */
interface MultiSessionAwareInterface
{
    /**
     * @param string $alias
     * @return string
     */
    public function getSessionName($alias);

    /**
     * @return array|string[]
     */
    public function getAliases();

    /**
     * @param string $alias
     * @return bool
     */
    public function hasRegisteredAlias($alias);

    /**
     * @param string $sessionAlias
     * @param string $key
     * @param mixed $value
     */
    public function saveSessionValue($sessionAlias, $key, $value);

    /**
     * @param string $sessionAlias
     * @param string $key
     * @param null|mixed $default
     * @return mixed
     */
    public function getSessionValue($sessionAlias, $key, $default = null);
}
