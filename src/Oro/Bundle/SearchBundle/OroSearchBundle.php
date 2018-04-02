<?php

namespace Oro\Bundle\SearchBundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\DependencyInjection\Compiler\FilterTypesPass;
use Oro\Bundle\SearchBundle\DependencyInjection\Compiler\ListenerExcludeSearchConnectionPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroSearchBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new ListenerExcludeSearchConnectionPass());
            $container->moveCompilerPassBefore(
                'Oro\Bundle\SearchBundle\DependencyInjection\Compiler\ListenerExcludeSearchConnectionPass',
                'Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass'
            );
        }

        $addTopicPass = AddTopicMetaPass::create()
            ->add(Topics::REINDEX, 'Search index reindex')
            ->add(Topics::INDEX_ENTITIES, 'Index entities by id')
            ->add(Topics::INDEX_ENTITY_TYPE, 'Index entities by class name')
            ->add(Topics::INDEX_ENTITY_BY_RANGE, 'Index range of entities of specified class')
            ->add(Topics::INDEX_ENTITY, 'Index single entity by id')
        ;
        $container->addCompilerPass($addTopicPass);
        $container->addCompilerPass(new FilterTypesPass());
    }
}
