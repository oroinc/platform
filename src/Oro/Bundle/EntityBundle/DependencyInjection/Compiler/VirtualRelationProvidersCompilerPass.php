<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

class VirtualRelationProvidersCompilerPass extends AbstractProviderCompilerPass
{
    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return 'oro_entity.virtual_relation_provider';
    }

    /**
     * {@inheritdoc}
     */
    protected function getService()
    {
        return 'oro_entity.virtual_relation_provider.chain';
    }
}
