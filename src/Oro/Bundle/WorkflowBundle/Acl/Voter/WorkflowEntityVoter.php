<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowEntityAclIdentityRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;

class WorkflowEntityVoter implements VoterInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected $supportedAttributes = array('EDIT', 'DELETE');

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
     * @param ManagerRegistry $registry
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ManagerRegistry $registry, DoctrineHelper $doctrineHelper)
    {
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, $this->supportedAttributes);
    }

    /**
     * Check whether at least one of the the attributes is supported
     *
     * @param array $attributes
     * @return bool
     */
    protected function supportsAttributes(array $attributes)
    {
        $supportsAttributes = false;
        foreach ($attributes as $attribute) {
            if ($this->supportsAttribute($attribute)) {
                $supportsAttributes = true;
                break;
            }
        }

        return $supportsAttributes;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        $this->loadEntityAcls();

        return array_key_exists($class, $this->entityAcls);
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object || !is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        // both entity and identity objects are supported
        $class = $this->getEntityClass($object);

        try {
            $identifier = $this->getEntityIdentifier($object);
        } catch (NotManageableEntityException $e) {
            return self::ACCESS_ABSTAIN;
        }

        if (null === $identifier) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->getPermission($class, $identifier, $attributes);
    }

    /**
     * @param string $class
     * @param int $identifier
     * @param array $attributes
     * @return int
     */
    protected function getPermission($class, $identifier, array $attributes)
    {
        // cheap performance check (no DB interaction)
        if (!$this->supportsAttributes($attributes)) {
            return self::ACCESS_ABSTAIN;
        }

        // expensive performance check (includes DB interaction)
        if (!$this->supportsClass($class)) {
            return self::ACCESS_ABSTAIN;
        }

        $result = self::ACCESS_ABSTAIN;
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $permission = $this->getPermissionForAttribute($class, $identifier, $attribute);

            // if not abstain or changing from granted to denied
            if ($result === self::ACCESS_ABSTAIN && $permission !== self::ACCESS_ABSTAIN
                || $result === self::ACCESS_GRANTED && $permission === self::ACCESS_DENIED
            ) {
                $result = $permission;
            }

            // if one of attributes is denied then access should be denied for all attributes
            if ($result === self::ACCESS_DENIED) {
                break;
            }
        }

        return $result;
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

    protected function loadEntityPermissions($class, $identifier)
    {
        if (array_key_exists($identifier, $this->entityAcls[$class]['entities'])) {
            return;
        }

        // default permissions
        $this->entityAcls[$class]['entities'][$identifier] = array(
            'update' => true,
            'delete' => true,
        );

        /** @var WorkflowEntityAclIdentityRepository $repository */
        $repository = $this->registry->getRepository('OroWorkflowBundle:WorkflowEntityAclIdentity');
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
        $entityAcls = $this->registry->getRepository('OroWorkflowBundle:WorkflowEntityAcl')->findAll();

        $this->entityAcls = array();
        foreach ($entityAcls as $entityAcl) {
            $entityClass = $entityAcl->getEntityClass();

            if (!array_key_exists($entityClass, $this->entityAcls)) {
                $this->entityAcls[$entityClass] = array(
                    'acls' => array(),
                    'entities' => array(),
                );
            }

            $this->entityAcls[$entityClass]['acls'][$entityAcl->getId()] = $entityAcl;
        }
    }

    /**
     * @param object $object
     * @return string
     */
    protected function getEntityClass($object)
    {
        if ($object instanceof ObjectIdentityInterface) {
            $class = $object->getType();
        } else {
            $class = $this->doctrineHelper->getEntityClass($object);
        }

        return ClassUtils::getRealClass($class);
    }

    /**
     * @param object $object
     * @return int|null
     */
    protected function getEntityIdentifier($object)
    {
        if ($object instanceof ObjectIdentityInterface) {
            $identifier = $object->getIdentifier();
            if (!filter_var($identifier, FILTER_VALIDATE_INT)) {
                $identifier = null;
            } else {
                $identifier = (int)$identifier;
            }
        } else {
            $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object, false);
        }

        return $identifier;
    }
}
