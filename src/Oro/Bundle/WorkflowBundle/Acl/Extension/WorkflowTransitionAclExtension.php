<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AbstractSimpleAccessLevelAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionMaskBuilder as MaskBuilder;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowTransitionAclExtension extends AbstractSimpleAccessLevelAclExtension
{
    const PERMISSION_PERFORM = 'PERFORM_TRANSITION';

    /** @var WorkflowRegistry */
    protected $workflowRegistry;

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
        parent::__construct($objectIdAccessor, $metadataProvider, $entityOwnerAccessor, $decisionMaker);
        $this->workflowRegistry = $workflowRegistry;

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
        throw new \LogicException('Workflow Transition ACL Extension does not support "getObjectIdentity" method');
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
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        if (!$this->isSupportedObject($object)) {
            return true;
        }

        return $this->isAccessGranted($triggeredMask, $object, $securityToken);
    }

    /**
     * Gets class name for given object
     *
     * @param $object
     *
     * @return string
     */
    protected function getObjectClassName($object)
    {
        if ($object instanceof ObjectIdentityInterface) {
            $workflowName = $object->getType();
        } elseif (is_string($object)) {
            $workflowName = $id = $group = null;
            if (ObjectIdentityHelper::isFieldEncodedKey($object)) {
                $object = ObjectIdentityHelper::decodeEntityFieldInfo($object)[0];
            }
            $this->parseDescriptor($object, $workflowName, $id, $group);
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected argument of type "string, Symfony\Component\Security\Acl\Domain\ObjectIdentity", '
                    . '"%s" given.',
                    is_object($object) ? get_class($object) : gettype($object)
                )
            );
        }

        return $this->workflowRegistry->getWorkflow($workflowName)->getDefinition()->getRelatedEntity();
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaskBuilderConst($constName)
    {
        return MaskBuilder::getConst($constName);
    }
}
