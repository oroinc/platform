<?php

namespace Oro\Bundle\ImportExportBundle\Twig;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render import/export buttons:
 *   - get_import_export_configuration
 */
class GetImportExportConfigurationExtension extends AbstractExtension
{
    /**
     * @var ImportExportConfigurationRegistryInterface
     */
    private $configurationRegistry;

    /**
     * @param ImportExportConfigurationRegistryInterface $configurationRegistry
     */
    public function __construct(ImportExportConfigurationRegistryInterface $configurationRegistry)
    {
        $this->configurationRegistry = $configurationRegistry;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_import_export_configuration', [$this, 'getConfiguration'])
        ];
    }

    /**
     * @param string $alias
     *
     * @return ImportExportConfigurationInterface[]
     */
    public function getConfiguration(string $alias): array
    {
        return $this->configurationRegistry->getConfigurations($alias);
    }
}
