<?php

namespace Oro\Bundle\GaufretteBundle\DependencyInjection\Factory;

/**
 * A factory to configure Gaufrette adapters for a local filesystem.
 */
class LocalConfigurationFactory implements ConfigurationFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAdapterConfiguration(string $configString): array
    {
        return [
            'local' => [
                'directory' => $configString
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return 'local';
    }

    /**
     * {@inheritdoc}
     */
    public function getHint(): string
    {
        return
            'The configuration string is "local:{directory}",'
            . ' for example "local:%kernel.project_dir%/public/media".';
    }
}
