<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

class VirtualFieldProvidersCompilerPass extends AbstractProviderCompilerPass
{
    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return 'oro_entity.virtual_field_provider';
    }

    /**
     * {@inheritdoc}
     */
    protected function getService()
    {
        return 'oro_entity.virtual_field_provider.chain';
    }
}
