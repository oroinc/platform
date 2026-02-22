<?php

namespace Oro\Bundle\ImportExportBundle\Twig;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render import/export buttons:
 *   - get_import_export_configuration
 * Provides a Twig filter to remove namespaces for a PHP class name:
 *    - basename
 */
class ImportExportExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('get_import_export_configuration', [$this, 'getConfiguration'])
        ];
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('basename', [$this, 'getBasename'])
        ];
    }

    /**
     * @return ImportExportConfigurationInterface[]
     */
    public function getConfiguration(string $alias): array
    {
        return $this->getImportExportConfigurationRegistry()->getConfigurations($alias);
    }

    public function getBasename(string $value): string
    {
        return basename(str_replace('\\', '/', $value));
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ImportExportConfigurationRegistryInterface::class
        ];
    }

    private function getImportExportConfigurationRegistry(): ImportExportConfigurationRegistryInterface
    {
        return $this->container->get(ImportExportConfigurationRegistryInterface::class);
    }
}
