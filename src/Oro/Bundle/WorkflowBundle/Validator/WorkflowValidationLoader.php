<?php

namespace Oro\Bundle\WorkflowBundle\Validator;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;

use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;

class WorkflowValidationLoader extends AbstractLoader
{
    /** @var WorkflowPermissionRegistry */
    protected $permissionRegistry;

    /** @var RestrictionManager */
    protected $restrictionManager;

    /** @var ServiceLink */
    protected $emLink;

    protected $dbCheck;

    /** @var string[] */
    protected $requiredTables = [
        'oro_workflow_entity_acl',
        'oro_workflow_entity_acl_ident',
        'oro_workflow_restriction',
        'oro_workflow_restriction_ident'
    ];

    /**
     * @param WorkflowPermissionRegistry $permissionRegistry
     * @param RestrictionManager         $restrictionManager
     * @param ServiceLink                $emLink A link to the EntityManager
     */
    public function __construct(
        WorkflowPermissionRegistry $permissionRegistry,
        RestrictionManager $restrictionManager,
        ServiceLink $emLink
    ) {
        $this->permissionRegistry = $permissionRegistry;
        $this->restrictionManager = $restrictionManager;
        $this->emLink             = $emLink;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        if (!$this->checkDatabase()) {
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

    /**
     * @return bool
     */
    protected function checkDatabase()
    {
        if (null !== $this->dbCheck) {
            return $this->dbCheck;
        }

        $this->dbCheck = SafeDatabaseChecker::tablesExist(
            $this->getEntityManager()->getConnection(),
            $this->requiredTables
        );

        return $this->dbCheck;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->emLink->getService();
    }
}
