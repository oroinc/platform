<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository;

class WorkflowPermissionRegistry
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
     *                  'UPDATE' => true|false,
     *                  'DELETE' => true|false
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
     * @param $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        $this->loadEntityAcls();

        return array_key_exists($class, $this->entityAcls);
    }

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getEntityPermissions($entity)
    {
        $class      = $this->doctrineHelper->getEntityClass($entity);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        return $this->getPermissionByClassAndIdentifier($class, $identifier);
    }

    /**
     * @param $class
     * @param $identifier
     *
     * @return array
     */
    public function getPermissionByClassAndIdentifier($class, $identifier)
    {
        $this->loadEntityPermissions($class, $identifier);

        return $this->entityAcls[$class]['entities'][$identifier];
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
                    'acls'     => [],
                    'entities' => [],
                ];
            }

            $this->entityAcls[$entityClass]['acls'][$entityAcl->getId()] = $entityAcl;
        }
    }

    /**
     * @param string $class
     * @param mixed  $identifier
     */
    protected function loadEntityPermissions($class, $identifier)
    {
        $this->loadEntityAcls();

        if (isset($this->entityAcls[$class]['entities']) &&
            array_key_exists($identifier, $this->entityAcls[$class]['entities'])
        ) {
            return;
        }

        // default permissions
        $this->entityAcls[$class]['entities'][$identifier] = [
            'UPDATE' => true,
            'DELETE' => true,
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
            if ($this->entityAcls[$class]['entities'][$identifier]['UPDATE'] && !$entityAcl->isUpdatable()) {
                $this->entityAcls[$class]['entities'][$identifier]['UPDATE'] = false;
            }
            if ($this->entityAcls[$class]['entities'][$identifier]['DELETE'] && !$entityAcl->isDeletable()) {
                $this->entityAcls[$class]['entities'][$identifier]['DELETE'] = false;
            }
        }
    }
}
