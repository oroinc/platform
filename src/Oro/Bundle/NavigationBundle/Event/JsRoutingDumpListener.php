<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Updates default values of the format and target options for routing command.
 */
class JsRoutingDumpListener
{
    /** @var DynamicAssetVersionManager */
    private $assetVersionManager;

    /** @var string */
    private $projectDir;

    /** @var string */
    private $filenamePrefix;

    public function __construct(
        DynamicAssetVersionManager $assetVersionManager,
        string $projectDir,
        string $filenamePrefix
    ) {
        $this->assetVersionManager = $assetVersionManager;
        $this->projectDir = $projectDir;
        $this->filenamePrefix = $filenamePrefix;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command || 'fos:js-routing:dump' !== $command->getName()) {
            return;
        }

        $this->assetVersionManager->updateAssetVersion('routing');

        $definition = $command->getDefinition();
        $definition->getOption('format')
            ->setDefault('json');

        $input = $event->getInput();

        $definition->getOption('target')
            ->setDefault(
                implode(
                    DIRECTORY_SEPARATOR,
                    [
                        $this->projectDir,
                        'public',
                        'media',
                        'js',
                        sprintf('%s.%s', $this->getFilename(), $input->getOption('format'))
                    ]
                )
            );
    }

    private function getFilename(): string
    {
        return implode('_', array_filter([rtrim($this->filenamePrefix, '_'), 'routes']));
    }
}
