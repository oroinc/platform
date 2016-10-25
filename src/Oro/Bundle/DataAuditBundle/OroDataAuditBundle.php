<?php

namespace Oro\Bundle\DataAuditBundle;

use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\DependencyInjection\Compiler\DisableDataAuditListenerPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroDataAuditBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DisableDataAuditListenerPass());
        $container->addCompilerPass(
            AddTopicMetaPass::create()
                ->add(Topics::ENTITIES_CHANGED, 'Creates audit for usual entity properties')
                ->add(Topics::ENTITIES_RELATIONS_CHANGED, '[Internal] Creates audit for entity rel.')
                ->add(Topics::ENTITIES_INVERSED_RELATIONS_CHANGED, '[Internal] Create audit for entity inverse rel.')
        );
    }
}
