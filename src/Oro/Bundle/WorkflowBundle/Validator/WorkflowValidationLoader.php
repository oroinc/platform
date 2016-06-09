<?php

namespace Oro\Bundle\WorkflowBundle\Validator;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;

use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;

class WorkflowValidationLoader extends AbstractLoader
{
    /** @var WorkflowPermissionRegistry */
    protected $permissionRegistry;

    /** @var RestrictionManager */
    protected $restrictionManager;

    /**
     * @param WorkflowPermissionRegistry $permissionRegistry
     * @param RestrictionManager         $restrictionManager
     */
    public function __construct(WorkflowPermissionRegistry $permissionRegistry, RestrictionManager $restrictionManager)
    {
        $this->permissionRegistry = $permissionRegistry;
        $this->restrictionManager = $restrictionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        $class = $metadata->getClassName();

        if ($this->permissionRegistry->supportsClass($class, false) ||
            $this->restrictionManager->hasEntityClassRestrictions($class, false)
        ) {
            $metadata->addConstraint(
                $this->newConstraint('Oro\Bundle\WorkflowBundle\Validator\Constraints\WorkflowEntity')
            );

            return true;
        }

        return false;
    }
}
