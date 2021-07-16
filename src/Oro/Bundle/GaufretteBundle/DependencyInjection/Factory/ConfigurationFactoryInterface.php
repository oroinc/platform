<?php

namespace Oro\Bundle\GaufretteBundle\DependencyInjection\Factory;

/**
 * Represents a factory to configure Gaufrette adapters via parameters.yml.
 */
interface ConfigurationFactoryInterface
{
    /**
     * Configures the container services for message queue transport.
     */
    public function getAdapterConfiguration(string $configString): array;

    /**
     * Gets a string that represents a Gaufrette adapter type.
     */
    public function getKey(): string;

    /**
     * Gets a string that shows how to configure a Gaufrette adapter type.
     */
    public function getHint(): string;
}
