<?php

namespace Oro\Bundle\CommentBundle;

use Oro\Bundle\CommentBundle\DependencyInjection\Compiler\ConfigureApiPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The CommentBundle bundle class.
 */
class OroCommentBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigureApiPass());
    }
}
