<?php

namespace Oro\Bundle\GaufretteBundle\DependencyInjection\Factory;

/**
 * Represents a factory to configure Gaufrette adapters via parameters.yml.
 */
interface ConfigurationFactoryInterface
{
    /**
     * Configures the container services for message queue transport.
     *
     * @param string $configString
     *
     * @return array
     */
    public function getAdapterConfiguration(string $configString): array;

    /**
     * Gets a string that represents a Gaufrette adapter type.
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Gets a string that shows how to configure a Gaufrette adapter type.
     *
     * @return string
     */
    public function getHint(): string;
}
