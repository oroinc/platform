<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionMaskBuilder as MaskBuilder;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WorkflowTransitionAclExtension extends AbstractWorkflowAclExtension
{
    const PERMISSION_PERFORM = 'PERFORM_TRANSITION';

    /**
     * @param ObjectIdAccessor                           $objectIdAccessor
     * @param OwnershipMetadataProviderInterface         $metadataProvider
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param WorkflowManager                            $workflowManager
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        OwnershipMetadataProviderInterface $metadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        WorkflowManager $workflowManager
    ) {
        parent::__construct(
            $objectIdAccessor,
            $metadataProvider,
            $entityOwnerAccessor,
            $decisionMaker,
            $workflowManager
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
        return WorkflowAclExtension::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        return true;
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
    public function getAllowedPermissions(ObjectIdentity $oid, $fieldName = null, $aclGroup = null)
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
    public function getServiceBits($mask)
    {
        return $mask & MaskBuilder::SERVICE_BITS;
    }

    /**
     * {@inheritdoc}
     */
    public function removeServiceBits($mask)
    {
        return $mask & MaskBuilder::REMOVE_SERVICE_BITS;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseDescriptor($descriptor, &$type, &$id, &$group)
    {
        $descriptor = ObjectIdentityHelper::removeFieldName($descriptor);

        return parent::parseDescriptor($descriptor, $type, $id, $group);
    }

    /**
     * Process start transition. For the start transitions with init options return null class cause this transition
     * will start from the entity from the init options.
     *
     * {@inheritdoc}
     */
    protected function getObjectClassName($object)
    {
        if (is_string($object)) {
            $workflowName = $id = $group = null;
            $this->parseDescriptor($object, $workflowName, $id, $group);
            $fieldName = ObjectIdentityHelper::decodeEntityFieldInfo($object)[1];
            list($transitionName, $fromStep, $toStep) = explode('|', $fieldName);
            // detect that given transition is start
            if ('' === $fromStep) {
                $workflow = $this->getWorkflowManager()->getWorkflow($workflowName);
                $transition = $workflow->getTransitionManager()->getTransition($transitionName);
                if ($transition->isStart() && !$transition->isEmptyInitOptions()) {
                    return null;
                }
            }
        }

        return parent::getObjectClassName($object);
    }
}
