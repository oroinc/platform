<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowMaskBuilder as MaskBuilder;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

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
     * @param MetadataProviderInterface                  $metadataProvider
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param WorkflowRegistry                           $workflowRegistry
     * @param WorkflowAclMetadataProvider                $workflowMetadataProvider
     * @param WorkflowTransitionAclExtension             $transitionAclExtension
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        MetadataProviderInterface $metadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        WorkflowRegistry $workflowRegistry,
        WorkflowAclMetadataProvider $workflowMetadataProvider,
        WorkflowTransitionAclExtension $transitionAclExtension
    ) {
        parent::__construct(
            $objectIdAccessor,
            $metadataProvider,
            $entityOwnerAccessor,
            $decisionMaker,
            $workflowRegistry
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
    public function getObjectIdentity($val)
    {
        if (is_string($val)) {
            return $this->fromDescriptor($val);
        } elseif ($val instanceof AclAnnotation) {
            return new ObjectIdentity(
                $val->getType(),
                ObjectIdentityHelper::buildType($val->getClass(), $val->getGroup())
            );
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
    public function adaptRootMask($rootMask, $object)
    {
        $permissions = $this->getPermissions($rootMask, true);
        if (!empty($permissions)) {
            $metadata = $this->getMetadata($object);
            foreach ($permissions as $permission) {
                $permissionMask = $this->getMaskBuilderConst('GROUP_' . $permission);
                $mask = $rootMask & $permissionMask;
                $accessLevel = $this->getAccessLevel($mask);
                if (!$metadata->hasOwner()) {
                    if ($accessLevel < AccessLevel::SYSTEM_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $this->getMaskBuilderConst('MASK_' . $permission . '_SYSTEM');
                    }
                } elseif ($metadata->isGlobalLevelOwned()) {
                    if ($accessLevel < AccessLevel::GLOBAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $this->getMaskBuilderConst('MASK_' . $permission . '_GLOBAL');
                    }
                } elseif ($metadata->isLocalLevelOwned()) {
                    if ($accessLevel < AccessLevel::LOCAL_LEVEL) {
                        $rootMask &= ~$this->removeServiceBits($mask);
                        $rootMask |= $this->getMaskBuilderConst('MASK_' . $permission . '_LOCAL');
                    }
                }
            }
        }

        return $rootMask;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaskBuilderConst($constName)
    {
        return MaskBuilder::getConst($constName);
    }

    /**
     * Constructs an ObjectIdentity for the given domain object
     *
     * @param string $descriptor
     *
     * @return ObjectIdentity
     * @throws \InvalidArgumentException
     */
    protected function fromDescriptor($descriptor)
    {
        $type = $id = $group = null;
        $this->parseDescriptor($descriptor, $type, $id, $group);

        if ($id === $this->getExtensionKey()) {
            return new ObjectIdentity($id, ObjectIdentityHelper::buildType($type, $group));
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported object identity descriptor: %s.', $descriptor)
        );
    }
}
