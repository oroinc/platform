<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * EntityManager was made as lazy in https://github.com/doctrine/DoctrineBundle/pull/559
 * but by some reasons this causes the following error in functional tests:
 * Doctrine\ORM\ORMInvalidArgumentException: A new entity was found through the relationship
 * 'Oro\Bundle\CustomerBundle\Entity\CustomerUser#organization' that was not configured to cascade
 * persist operations for entity: OroCRM.
 */
class UndoLazyEntityManagerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ('test' === $container->getParameter('kernel.environment')) {
            $container->getDefinition('doctrine.orm.entity_manager.abstract')->setLazy(false);
        }
    }
}
