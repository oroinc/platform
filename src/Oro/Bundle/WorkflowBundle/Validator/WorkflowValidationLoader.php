<?php

namespace Oro\Bundle\WorkflowBundle\Validator;

use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Validator\Constraints\WorkflowEntity;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Adds WorkflowEntity validation constraint to workflow related entities.
 */
class WorkflowValidationLoader extends AbstractLoader implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            DatabaseChecker::class,
            WorkflowPermissionRegistry::class,
            RestrictionManager::class
        ];
    }

    #[\Override]
    public function loadClassMetadata(ClassMetadata $metadata): bool
    {
        if (!$this->getDatabaseChecker()->checkDatabase()) {
            return false;
        }

        if (!$this->isWorkflowEntityConstraintRequired($metadata->getClassName())) {
            return false;
        }

        $metadata->addConstraint($this->newConstraint(WorkflowEntity::class));

        return true;
    }

    private function isWorkflowEntityConstraintRequired(string $className): bool
    {
        return
            $this->getPermissionRegistry()->supportsClass($className, false)
            || $this->getRestrictionManager()->hasEntityClassRestrictions($className, false);
    }

    private function getDatabaseChecker(): DatabaseChecker
    {
        return $this->container->get(DatabaseChecker::class);
    }

    private function getPermissionRegistry(): WorkflowPermissionRegistry
    {
        return $this->container->get(WorkflowPermissionRegistry::class);
    }

    private function getRestrictionManager(): RestrictionManager
    {
        return $this->container->get(RestrictionManager::class);
    }
}
