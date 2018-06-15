<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Handles entity repository definition
 */
class EntityRepositoryCompilerPass implements CompilerPassInterface
{
    const ABSTRACT_REPOSITORY = 'oro_entity.abstract_repository';
    const REPOSITORY_FACTORY = 'oro_entity.repository.factory';
    const ORM_CONFIGURATION = 'doctrine.orm.configuration';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(static::REPOSITORY_FACTORY) ||
            !$container->hasDefinition(static::ORM_CONFIGURATION)
        ) {
            return;
        }

        // get all repository services
        $repositoryDefinitions = $this->getChildDefinitions($container, static::ABSTRACT_REPOSITORY);

        // set correct repository service arguments for factory and merge repository definitions by class
        // there might be several repositories with the same class in case of service inheritance or service decoration
        $definitionsByClass = $this->getDefinitionsByClass($container, $repositoryDefinitions);

        // check for private services, validation should be applied after merge of definitions by class
        // because parent services already might become private
        $definitionIdsByClass = $this->getDefinitionIdsByClass($definitionsByClass);

        // set repository services array to repository factory
        $repositoryFactory = $container->getDefinition(static::REPOSITORY_FACTORY);
        $repositoryFactory->replaceArgument(1, $definitionIdsByClass);

        // get all orm configuration services
        $doctrineConfigurationDefinitions = $this->getChildDefinitions($container, static::ORM_CONFIGURATION);

        // use entity repository factory instead of default one
        foreach ($doctrineConfigurationDefinitions as $doctrineConfigurationDefinition) {
            $doctrineConfigurationDefinition->addMethodCall(
                'setRepositoryFactory',
                [new Reference(static::REPOSITORY_FACTORY)]
            );
        }
    }

    /**
     * @param array $definitionsByClass
     * @return array ['<className>' => '<definitionId>'], ...]
     * @throws LogicException
     */
    private function getDefinitionIdsByClass(array $definitionsByClass)
    {
        $definitionIdsByClass = [];
        foreach ($definitionsByClass as $className => $definitionData) {
            /** @var Definition $definition */
            $definition = $definitionData['definition'];
            $definitionId = $definitionData['id'];

            if (!$definition->isPublic()) {
                throw new LogicException(
                    sprintf('Repository service %s for class %s must be public', $definitionId, $className)
                );
            }

            $definitionIdsByClass[$className] = $definitionId;
        }

        return $definitionIdsByClass;
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition[] $repositoryDefinitions
     * @return array ['<className>' => ['id' => '<definitionId>', 'definition' => <Definition>], ...]
     */
    private function getDefinitionsByClass(ContainerBuilder $container, array $repositoryDefinitions)
    {
        $definitionsByClass = [];
        foreach ($repositoryDefinitions as $definitionId => $definition) {
            $className = $this->getRepositoryEntityClass($container, $definition, $definitionId);

            $repositoryClassName = $this->getRepositoryClass($container, $definition);
            if ($repositoryClassName) {
                $definition->setArguments([$className, $repositoryClassName]);
            }

            $definitionsByClass[$className] = ['id' => $definitionId, 'definition' => $definition];
        }

        return $definitionsByClass;
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @param string $definitionId
     * @return string
     * @throws LogicException
     */
    private function getRepositoryEntityClass(ContainerBuilder $container, Definition $definition, $definitionId)
    {
        $arguments = $this->getArguments($container, $definition);

        if (empty($arguments) || count($arguments) > 2) {
            throw new LogicException(
                sprintf(
                    'Repository service %s might accept only entity class and repository class as arguments',
                    $definitionId
                )
            );
        }

        $entityClass = reset($arguments);
        if (strpos($entityClass, '%') === 0) {
            $classParameter = substr($entityClass, 1, -1);
            $entityClass = $container->getParameter($classParameter);
        }
        if (!class_exists($entityClass)) {
            throw new LogicException(
                sprintf('Entity class %s defined at repository service %s doesn\'t exist', $entityClass, $definitionId)
            );
        }

        return $entityClass;
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @return null|string
     */
    private function getRepositoryClass(ContainerBuilder $container, Definition $definition)
    {
        $repositoryClass = $definition->getClass();

        if ($repositoryClass) {
            if (strpos($repositoryClass, '%') === 0) {
                $classParameter = substr($repositoryClass, 1, -1);
                $repositoryClass = $container->getParameter($classParameter);
            }
            return $repositoryClass;
        } elseif ($definition instanceof ChildDefinition
            && $definition->getParent() !== static::ABSTRACT_REPOSITORY
        ) {
            $parentDefinition = $container->getDefinition($definition->getParent());
            return $this->getRepositoryClass($container, $parentDefinition);
        } else {
            return null;
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @return array
     */
    private function getArguments(ContainerBuilder $container, Definition $definition)
    {
        $arguments = $definition->getArguments();
        if (0 !== count($arguments)) {
            return $arguments;
        } elseif ($definition instanceof ChildDefinition) {
            $parentDefinition = $container->getDefinition($definition->getParent());
            return $this->getArguments($container, $parentDefinition);
        } else {
            return $arguments;
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $parentDefinitionId
     * @return ChildDefinition[]
     */
    private function getChildDefinitions(ContainerBuilder $container, $parentDefinitionId)
    {
        $definitions = [];

        foreach ($container->getDefinitions() as $definitionId => $definition) {
            if ($definition instanceof ChildDefinition && $definition->getParent() === $parentDefinitionId) {
                if (!$definition->isAbstract()) {
                    $definitions[$definitionId] = $definition;
                }
                $definitions = array_merge($definitions, $this->getChildDefinitions($container, $definitionId));
            }
        }

        return $definitions;
    }
}
