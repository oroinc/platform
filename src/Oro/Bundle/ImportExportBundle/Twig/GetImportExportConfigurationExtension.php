<?php

namespace Oro\Bundle\ImportExportBundle\Twig;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface;

class GetImportExportConfigurationExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction('get_import_export_configuration', [$this, 'getConfiguration'])
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
