<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;

class WorkflowEntityVoter extends AbstractEntityVoter
{
    /**
     * {@inheritdoc}
     */
    protected $supportedAttributes = ['DELETE'];

    /** @var WorkflowPermissionRegistry */
    protected $permissionRegistry;

    /**
     * {@inheritdoc}
     * @param WorkflowPermissionRegistry $permissionRegistry
     */
    public function __construct(DoctrineHelper $doctrineHelper, WorkflowPermissionRegistry $permissionRegistry)
    {
        parent::__construct($doctrineHelper);

        $this->permissionRegistry = $permissionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $this->permissionRegistry->supportsClass($class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        $permissions = $this->permissionRegistry->getPermissionByClassAndIdentifier($class, $identifier);

        switch ($attribute) {
            case 'DELETE':
                return $permissions[$attribute]
                    ? self::ACCESS_GRANTED
                    : self::ACCESS_DENIED;

            default:
                return self::ACCESS_ABSTAIN;
        }
    }
}
