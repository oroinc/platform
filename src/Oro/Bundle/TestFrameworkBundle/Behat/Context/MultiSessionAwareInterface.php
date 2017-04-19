<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

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
