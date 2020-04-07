<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\Walker\OutputAstWalkerInterface;
use Oro\Component\DoctrineUtils\ORM\Walker\OutputResultModifierInterface;
use Oro\Component\DoctrineUtils\ORM\Walker\SqlWalker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configure output SQL walker.
 */
class SqlWalkerPass implements CompilerPassInterface
{
    public const TAG_NAME = 'oro_entity.sql_walker';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $doctrineConfiguration = $container->getDefinition('doctrine.orm.configuration');
        $doctrineConfiguration->addMethodCall(
            'setDefaultQueryHint',
            [
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                SqlWalker::class
            ]
        );

        $astWalkers = [];
        $outputModifiers = [];
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $attributes) {
            $walkerDefinition = $container->getDefinition($id);
            $walkerDefinition->setAbstract(true);
            $walkerDefinition->setPublic(false);
            $className = $walkerDefinition->getClass();
            if (is_a($className, OutputAstWalkerInterface::class, true)) {
                $astWalkers[] = $className;
            } elseif (is_a($className, OutputResultModifierInterface::class, true)) {
                $outputModifiers[] = $className;
            }
        }

        $doctrineConfiguration->addMethodCall(
            'setDefaultQueryHint',
            [
                OutputAstWalkerInterface::HINT_AST_WALKERS,
                $astWalkers
            ]
        );
        $doctrineConfiguration->addMethodCall(
            'setDefaultQueryHint',
            [
                OutputResultModifierInterface::HINT_RESULT_MODIFIERS,
                $outputModifiers
            ]
        );
    }
}
