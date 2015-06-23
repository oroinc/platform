<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

class EntityNameProviderPass extends AbstractProviderCompilerPass
{
    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return 'oro_entity.name_provider';
    }

    /**
     * {@inheritdoc}
     */
    protected function getService()
    {
        return 'oro_entity.entity_name_resolver';
    }
}
