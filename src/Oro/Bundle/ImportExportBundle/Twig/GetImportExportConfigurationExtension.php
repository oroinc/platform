<?php

namespace Oro\Bundle\ImportExportBundle\Twig;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render import/export buttons:
 *   - get_import_export_configuration
 */
class GetImportExportConfigurationExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
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
        return $this->getImportExportConfigurationRegistry()->getConfigurations($alias);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_importexport.configuration.registry' => ImportExportConfigurationRegistryInterface::class,
        ];
    }

    private function getImportExportConfigurationRegistry(): ImportExportConfigurationRegistryInterface
    {
        return $this->container->get('oro_importexport.configuration.registry');
    }
}
