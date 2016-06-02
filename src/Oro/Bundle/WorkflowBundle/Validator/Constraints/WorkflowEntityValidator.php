<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

class WorkflowEntityValidator extends ConstraintValidator
{
    /**
     * [
     *      '<entityClass>' => [
     *          'acls' => [
     *              <WorkflowEntityAcl.id> => <WorkflowEntityAcl>,
     *              ...
     *          ],
     *          'entities' => [
     *              <entityId> => [
     *                  'update' => true|false,
     *                  'delete' => true|false
     *              ],
     *              ...
     *          ]
     *      ],
     *      ...
     * ]
     *
     * @var array
     */
    protected $entityAcls;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProvider $configProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigProvider $configProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
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
        if ($root instanceof Form){
            if (WorkflowTransitionType::NAME === $root->getName()) {
                return;
            }
        }

        if (!$this->configProvider->hasConfig($value)){
            return;
        }

        $config = $this->configProvider->getConfig($value);
        if (!$config->get('active_workflow', false, false)) {
            return;
        }

        $permissions = $this->getEntityPermissions($value);
        if ($permissions['update'] === false) {
            // @todo: checks if entity has changes
            $this->context->addViolation($constraint->updateEntityMessage);
        }

        // @todo: Add Violation for fields atPath here
    }

    /**
     * @param object $value
     *
     * @return mixed
     */
    protected function getEntityPermissions($value)
    {
        $this->loadEntityAcls();

        $class      = $this->doctrineHelper->getEntityClass($value);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($value);

        $this->loadEntityPermissions($class, $identifier);

        return $this->entityAcls[$class]['entities'][$identifier];
    }

    /**
     * @param string $class
     * @param mixed $identifier
     */
    protected function loadEntityPermissions($class, $identifier)
    {
        if (array_key_exists($identifier, $this->entityAcls[$class]['entities'])) {
            return;
        }

        // default permissions
        $this->entityAcls[$class]['entities'][$identifier] = [
            'update' => true,
            'delete' => true,
        ];

        /** @var WorkflowEntityAclIdentityRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroWorkflowBundle:WorkflowEntityAclIdentity');
        $identities = $repository->findByClassAndIdentifier($class, $identifier);

        foreach ($identities as $identity) {
            $aclId = $identity->getAcl()->getId();
            if (empty($this->entityAcls[$class]['acls'][$aclId])) {
                continue;
            }

            /** @var WorkflowEntityAcl $entityAcl */
            $entityAcl = $this->entityAcls[$class]['acls'][$aclId];
            if ($this->entityAcls[$class]['entities'][$identifier]['update'] && !$entityAcl->isUpdatable()) {
                $this->entityAcls[$class]['entities'][$identifier]['update'] = false;
            }
            if ($this->entityAcls[$class]['entities'][$identifier]['delete'] && !$entityAcl->isDeletable()) {
                $this->entityAcls[$class]['entities'][$identifier]['delete'] = false;
            }
        }
    }

    /**
     * Load ACL entities and put it to internal cache
     */
    protected function loadEntityAcls()
    {
        if (null !== $this->entityAcls) {
            return;
        }

        /** @var WorkflowEntityAcl[] $entityAcls */
        $entityAcls = $this->doctrineHelper
            ->getEntityRepository('OroWorkflowBundle:WorkflowEntityAcl')
            ->findAll();

        $this->entityAcls = [];
        foreach ($entityAcls as $entityAcl) {
            $entityClass = $entityAcl->getEntityClass();

            if (!array_key_exists($entityClass, $this->entityAcls)) {
                $this->entityAcls[$entityClass] = [
                    'acls' => [],
                    'entities' => [],
                ];
            }

            $this->entityAcls[$entityClass]['acls'][$entityAcl->getId()] = $entityAcl;
        }
    }
}
