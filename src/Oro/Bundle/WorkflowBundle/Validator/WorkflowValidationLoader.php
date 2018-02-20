<?php

namespace Oro\Bundle\WorkflowBundle\Validator;

use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;

class WorkflowValidationLoader extends AbstractLoader
{
    /** @var WorkflowPermissionRegistry */
    protected $permissionRegistry;

    /** @var RestrictionManager */
    protected $restrictionManager;

    /** @var DatabaseChecker */
    protected $databaseChecker;

    /**
     * @param WorkflowPermissionRegistry $permissionRegistry
     * @param RestrictionManager         $restrictionManager
     * @param DatabaseChecker            $databaseChecker
     */
    public function __construct(
        WorkflowPermissionRegistry $permissionRegistry,
        RestrictionManager $restrictionManager,
        DatabaseChecker $databaseChecker
    ) {
        $this->permissionRegistry = $permissionRegistry;
        $this->restrictionManager = $restrictionManager;
        $this->databaseChecker = $databaseChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        if (!$this->databaseChecker->checkDatabase()) {
            return false;
        }

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
