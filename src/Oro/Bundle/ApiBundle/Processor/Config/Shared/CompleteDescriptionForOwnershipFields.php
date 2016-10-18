<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Adds human-readable descriptions for the ownership fields (e.g. owner, organization) of the entity.
 */
class CompleteDescriptionForOwnershipFields implements ProcessorInterface
{
    const OWNER_FIELD_DESCRIPTION = 'An Owner record represents the ownership capabilities of the record';
    const ORGANIZATION_FIELD_DESCRIPTION = 'An Organization record represents a real enterprise, business, firm,
        company or another organization, to which the record belongs';

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(ConfigProvider $entityConfigProvider)
    {
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * @param ContextInterface $context
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $targetAction = $context->getTargetAction();
        if (!$targetAction) {
            // descriptions cannot be set for undefined target action
            return;
        }

        $entityClass = $context->getClassName();

        if (!$this->entityConfigProvider->hasConfig($entityClass)) {
            // ownership fields are not available for non configurable entities
            return;
        }

        $entityConfig = $this->entityConfigProvider->getConfig($entityClass);
        $definition = $context->getResult();

        $ownerFieldName = $entityConfig->get('owner_field_name');
        if ($definition->hasField($ownerFieldName)) {
            $ownerField = $definition->getField($ownerFieldName);
            if (empty($ownerField->getDescription())) {
                $ownerField->setDescription(self::OWNER_FIELD_DESCRIPTION);
            }
        }

        $organizationFieldName = $entityConfig->get('organization_field_name');
        if ($definition->hasField($organizationFieldName)) {
            $organizationField = $definition->getField($organizationFieldName);
            if (empty($organizationField->getDescription())) {
                $organizationField->setDescription(self::ORGANIZATION_FIELD_DESCRIPTION);
            }
        }
    }
}
