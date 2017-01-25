<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\AbstractSimpleAccessLevelAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;

abstract class AbstractWorkflowAclExtension extends AbstractSimpleAccessLevelAclExtension
{
    /** @var WorkflowManagerRegistry */
    protected $workflowManagerRegistry;

    /**
     * @param ObjectIdAccessor                           $objectIdAccessor
     * @param MetadataProviderInterface                  $metadataProvider
     * @param EntityOwnerAccessor                        $entityOwnerAccessor
     * @param AccessLevelOwnershipDecisionMakerInterface $decisionMaker
     * @param WorkflowManagerRegistry                    $workflowManagerRegistry
     */
    public function __construct(
        ObjectIdAccessor $objectIdAccessor,
        MetadataProviderInterface $metadataProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        AccessLevelOwnershipDecisionMakerInterface $decisionMaker,
        WorkflowManagerRegistry $workflowManagerRegistry
    ) {
        parent::__construct($objectIdAccessor, $metadataProvider, $entityOwnerAccessor, $decisionMaker);
        $this->workflowManagerRegistry = $workflowManagerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjectClassName($object)
    {
        if ($object instanceof ObjectIdentityInterface) {
            $workflowName = $object->getType();
        } elseif (is_string($object)) {
            $workflowName = $id = $group = null;
            $this->parseDescriptor($object, $workflowName, $id, $group);
        } else {
            return ClassUtils::getRealClass($object);
        }

        return ObjectIdentityFactory::ROOT_IDENTITY_TYPE !== $workflowName
            ? $this->getWorkflowManager()->getWorkflow($workflowName)->getDefinition()->getRelatedEntity()
            : $workflowName;
    }

    /**
     * @return WorkflowManager
     */
    protected function getWorkflowManager()
    {
        return $this->workflowManagerRegistry->getManager();
    }
}
