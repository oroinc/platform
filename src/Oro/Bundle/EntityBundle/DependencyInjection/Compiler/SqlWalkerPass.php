<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\SqlWalker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SqlWalkerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('doctrine.orm.configuration')
            ->addMethodCall(
                'setDefaultQueryHint',
                [
                    Query::HINT_CUSTOM_OUTPUT_WALKER,
                    SqlWalker::class,
                ]
            );
    }
}
