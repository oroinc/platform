<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowMaskBuilder as MaskBuilder;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WorkflowAclExtension extends AbstractWorkflowAclExtension
{
    const NAME = 'workflow';

    const PERMISSION_VIEW    = 'VIEW_WORKFLOW';
    const PERMISSION_PERFORM = 'PERFORM_TRANSITIONS';

    /** @var WorkflowAclMetadataProvider */
    protected $workflowMetadataProvider;

    /** @var WorkflowTransitionAclExtension */
    protected $transitionAclExtension;

    /**
     * @param ObjectIdAccessor                           $objectIdAccessor
     * @param OwnershipMetadataProviderInterface         $metadataProvider
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param WorkflowManager                            $workflowManager
     * @param WorkflowAclMetadataProvider                $workflowMetadataProvider
     * @param WorkflowTransitionAclExtension             $transitionAclExtension
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        OwnershipMetadataProviderInterface $metadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        WorkflowManager $workflowManager,
        WorkflowAclMetadataProvider $workflowMetadataProvider,
        WorkflowTransitionAclExtension $transitionAclExtension
    ) {
        parent::__construct(
            $objectIdAccessor,
            $metadataProvider,
            $entityOwnerAccessor,
            $decisionMaker,
            $workflowManager
        );
        $this->workflowMetadataProvider = $workflowMetadataProvider;
        $this->transitionAclExtension = $transitionAclExtension;

        $this->permissions = [
            self::PERMISSION_VIEW,
            self::PERMISSION_PERFORM,
        ];

        $this->map = [
            self::PERMISSION_VIEW    => [
                MaskBuilder::MASK_VIEW_WORKFLOW_BASIC,
                MaskBuilder::MASK_VIEW_WORKFLOW_LOCAL,
                MaskBuilder::MASK_VIEW_WORKFLOW_DEEP,
                MaskBuilder::MASK_VIEW_WORKFLOW_GLOBAL,
                MaskBuilder::MASK_VIEW_WORKFLOW_SYSTEM,
            ],
            self::PERMISSION_PERFORM => [
                MaskBuilder::MASK_PERFORM_TRANSITIONS_BASIC,
                MaskBuilder::MASK_PERFORM_TRANSITIONS_LOCAL,
                MaskBuilder::MASK_PERFORM_TRANSITIONS_DEEP,
                MaskBuilder::MASK_PERFORM_TRANSITIONS_GLOBAL,
                MaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionKey()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $id)
    {
        return $id === $this->getExtensionKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldExtension()
    {
        return $this->transitionAclExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        return $this->workflowMetadataProvider->getMetadata();
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
    public function getObjectIdentity($val)
    {
        if (is_string($val)) {
            $type = $id = $group = null;
            $this->parseDescriptor($val, $type, $id, $group);
            if ($this->getExtensionKey() !== $id) {
                throw new \InvalidArgumentException(sprintf('Unsupported object identity descriptor: %s.', $val));
            }

            return new ObjectIdentity($id, ObjectIdentityHelper::buildType($type, $group));
        } elseif ($val instanceof AclAnnotation) {
            throw new \InvalidArgumentException('Workflow ACL Extension does not support ACL annotations.');
        }

        return $this->fromDomainObject($val);
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
    protected function getMaskBuilderConst($constName)
    {
        return MaskBuilder::getConst($constName);
    }
}
