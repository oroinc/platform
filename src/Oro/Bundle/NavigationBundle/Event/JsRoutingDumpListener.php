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
    private $filenamePrefix = '';

    /**
     * @param DynamicAssetVersionManager $assetVersionManager
     * @param string $projectDir
     */
    public function __construct(DynamicAssetVersionManager $assetVersionManager, string $projectDir)
    {
        $this->assetVersionManager = $assetVersionManager;
        $this->projectDir = $projectDir;
    }

    /**
     * @param string $filenamePrefix
     */
    public function setFilenamePrefix(string $filenamePrefix): void
    {
        $this->filenamePrefix = $filenamePrefix;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
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

    /**
     * @return string
     */
    private function getFilename(): string
    {
        return implode('_', array_filter([rtrim($this->filenamePrefix, '_'), 'routes']));
    }
}
