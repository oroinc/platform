<?php

namespace Oro\Bundle\SearchBundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicDescriptionPass;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The SearchBundle bundle class.
 */
class OroSearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $addTopicPass = AddTopicDescriptionPass::create()
            ->add(Topics::REINDEX, 'Search index reindex')
            ->add(Topics::INDEX_ENTITIES, 'Index entities by id')
            ->add(Topics::INDEX_ENTITY_TYPE, 'Index entities by class name')
            ->add(Topics::INDEX_ENTITY_BY_RANGE, 'Index range of entities of specified class')
            ->add(Topics::INDEX_ENTITY, 'Index single entity by id')
        ;
        $container->addCompilerPass($addTopicPass);
        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_search.extension.search_filter_bag',
            'oro_search.extension.search_filter.filter',
            'type'
        ));
    }
}
