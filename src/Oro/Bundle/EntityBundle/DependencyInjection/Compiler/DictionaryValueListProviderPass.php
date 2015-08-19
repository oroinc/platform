<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

class DictionaryValueListProviderPass extends AbstractProviderCompilerPass
{
    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return 'oro_entity.dictionary_value_list_provider';
    }

    /**
     * {@inheritdoc}
     */
    protected function getService()
    {
        return 'oro_entity.dictionary_value_list_provider';
    }
}
