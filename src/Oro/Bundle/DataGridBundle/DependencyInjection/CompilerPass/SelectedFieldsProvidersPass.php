<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects selected fields providers by tag and adds them to composite provider service.
 */
class SelectedFieldsProvidersPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    private const COMPOSITE_PROVIDER_ID = 'oro_datagrid.provider.selected_fields';
    private const TAG_NAME = 'oro_datagrid.selected_fields_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::COMPOSITE_PROVIDER_ID,
            self::TAG_NAME,
            'addSelectedFieldsProvider'
        );
    }
}
