<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Doctrine\ORM\Query;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
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
    use PriorityTaggedLocatorTrait;

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
                SqlWalker::class,
            ]
        );

        $astWalkers = [];
        $outputModifiers = [];
        foreach ($this->findAndSortTaggedServices(self::TAG_NAME, '', $container, false) as $reference) {
            $walkerDefinition = $container->getDefinition((string) $reference);
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
                $astWalkers,
            ]
        );
        $doctrineConfiguration->addMethodCall(
            'setDefaultQueryHint',
            [
                OutputResultModifierInterface::HINT_RESULT_MODIFIERS,
                $outputModifiers,
            ]
        );
    }
}
