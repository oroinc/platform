<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;

class WorkflowEntityVoter extends AbstractEntityVoter
{
    /**
     * @var array
     */
    protected $supportedAttributes = ['EDIT', 'DELETE'];

    /**
     * array(
     *      '<entityClass>' => array(
     *          'acls' => array(
     *              <WorkflowEntityAcl.id> => <WorkflowEntityAcl>,
     *              ...
     *          ),
     *          'entities' => array(
     *              <entityId> => array(
     *                  'update' => true|false,
     *                  'delete' => true|false
     *              ),
     *              ...
     *          )
     *      ),
     *      ...
     * )
     *
     * @var array
     */
    protected $entityAcls;

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        $this->loadEntityAcls();

        return array_key_exists($class, $this->entityAcls);
    }

    /**
     * @param string $class
     * @param int $identifier
     * @param string $attribute
     * @return int
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        $this->loadEntityAcls();
        $this->loadEntityPermissions($class, $identifier);

        switch ($attribute) {
            case 'EDIT':
                return $this->entityAcls[$class]['entities'][$identifier]['update']
                    ? self::ACCESS_GRANTED
                    : self::ACCESS_DENIED;

            case 'DELETE':
                return $this->entityAcls[$class]['entities'][$identifier]['delete']
                    ? self::ACCESS_GRANTED
                    : self::ACCESS_DENIED;

            default:
                return self::ACCESS_ABSTAIN;
        }
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
        $repository = $this->doctrineHelper->getRepository('OroWorkflowBundle:WorkflowEntityAclIdentity');
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
            ->getRepository('OroWorkflowBundle:WorkflowEntityAcl')
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
