<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionMaskBuilder as MaskBuilder;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowTransitionAclExtension extends AbstractWorkflowAclExtension
{
    const PERMISSION_PERFORM = 'PERFORM_TRANSITION';

    /**
     * @param ObjectIdAccessor                           $objectIdAccessor
     * @param MetadataProviderInterface                  $metadataProvider
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param WorkflowRegistry                           $workflowRegistry
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        MetadataProviderInterface $metadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        WorkflowRegistry $workflowRegistry
    ) {
        parent::__construct(
            $objectIdAccessor,
            $metadataProvider,
            $entityOwnerAccessor,
            $decisionMaker,
            $workflowRegistry
        );

        $this->permissions = [
            self::PERMISSION_PERFORM,
        ];

        $this->map = [
            self::PERMISSION_PERFORM => [
                MaskBuilder::MASK_PERFORM_TRANSITION_BASIC,
                MaskBuilder::MASK_PERFORM_TRANSITION_LOCAL,
                MaskBuilder::MASK_PERFORM_TRANSITION_DEEP,
                MaskBuilder::MASK_PERFORM_TRANSITION_GLOBAL,
                MaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionKey()
    {
        throw new \LogicException('Workflow Transition ACL Extension does not support "getExtensionKey" method.');
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        throw new \LogicException('Workflow Transition ACL Extension does not support "supports" method.');
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        throw new \LogicException('Workflow Transition ACL Extension does not support "getClasses" method.');
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentity($val)
    {
        throw new \LogicException('Workflow Transition ACL Extension does not support "getObjectIdentity" method.');
    }

    /**
     * {@inheritdoc}
     */
    public function adaptRootMask($rootMask, $object)
    {
        throw new \LogicException('Workflow Transition ACL Extension does not support "adaptRootMask" method.');
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null)
    {
        return $this->permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        if (!$this->isSupportedObject($object)) {
            return true;
        }

        return $this->isAccessGranted($triggeredMask, $object, $securityToken);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskPattern($mask)
    {
        return MaskBuilder::getPatternFor($mask);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaskBuilder($permission)
    {
        return new MaskBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMaskBuilders()
    {
        return [new MaskBuilder()];
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaskBuilderConst($constName)
    {
        return MaskBuilder::getConst($constName);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseDescriptor($descriptor, &$type, &$id, &$group)
    {
        $descriptor = ObjectIdentityHelper::removeFieldName($descriptor);

        return parent::parseDescriptor($descriptor, $type, $id, $group);
    }
}
