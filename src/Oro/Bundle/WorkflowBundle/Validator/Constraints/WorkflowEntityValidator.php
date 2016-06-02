<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;

class WorkflowEntityValidator extends ConstraintValidator
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var WorkflowPermissionRegistry */
    protected $permissionRegistry;

    /**
     * @param EntityManager              $entityManager
     * @param DoctrineHelper             $doctrineHelper
     * @param ConfigProvider             $configProvider
     * @param WorkflowPermissionRegistry $permissionRegistry
     */
    public function __construct(
        EntityManager $entityManager,
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        WorkflowPermissionRegistry $permissionRegistry
    ) {
        $this->entityManager      = $entityManager;
        $this->doctrineHelper     = $doctrineHelper;
        $this->configProvider     = $configProvider;
        $this->permissionRegistry = $permissionRegistry;
    }

    /**
     * {@inheritdoc}
     * @param WorkflowEntity $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_object($value)) {
            return;
        }

        // Skip changes for workflow transition form
        $root = $this->context->getRoot();
        if ($root instanceof Form) {
            if (WorkflowTransitionType::NAME === $root->getName()) {
                return;
            }
        }

        if (!$this->configProvider->hasConfig($value)) {
            return;
        }

        $config = $this->configProvider->getConfig($value);
        if (!$config->get('active_workflow', false, false)) {
            return;
        }

        if ($this->doctrineHelper->isNewEntity($value)) {
            // @todo: add restrictions for the new entity if needed
        } else {
            $permissions = $this->permissionRegistry->getEntityPermissions($value);
            if ($permissions['UPDATE'] === false || $this->permissionRegistry->hasRestrictedEntityFields($value)) {
                $unitOfWork = $this->entityManager->getUnitOfWork();
                $class      = $this->entityManager->getClassMetadata($this->configProvider->getClassName($value));
                $unitOfWork->computeChangeSet($class, $value);
                if ($permissions['UPDATE'] === false) {
                    if ($unitOfWork->isScheduledForUpdate($value)) {
                        $this->context->addViolation($constraint->updateEntityMessage);
                    }
                } else {
                    $changesSet       = $unitOfWork->getEntityChangeSet($value);
                    $restrictedFields = array_flip($this->permissionRegistry->getRestrictedEntityFields($value));
                    if ($fields = array_intersect_key($changesSet, $restrictedFields)) {
                        foreach ($fields as $key => $value) {
                            // @todo: Check $value for restrictions mode: disallow
                            
                            /** @var ExecutionContextInterface $context */
                            $context = $this->context;
                            $context
                                ->buildViolation(
                                    $constraint->updateFieldMessage
                                )
                                ->atPath($key)
                                ->addViolation();
                        }
                    }
                }
            }
        }
    }
}
