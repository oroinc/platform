<?php

namespace Oro\Bundle\GaufretteBundle\DependencyInjection\Factory;

/**
 * A factory to configure Gaufrette adapters for a local filesystem.
 */
class LocalConfigurationFactory implements ConfigurationFactoryInterface
{
    #[\Override]
    public function getAdapterConfiguration(string $configString): array
    {
        return [
            'local' => [
                'directory' => $configString
            ]
        ];
    }

    #[\Override]
    public function getKey(): string
    {
        return 'local';
    }

    #[\Override]
    public function getHint(): string
    {
        return
            'The configuration string is "local:{directory}",'
            . ' for example "local:%kernel.project_dir%/public/media".';
    }
}
