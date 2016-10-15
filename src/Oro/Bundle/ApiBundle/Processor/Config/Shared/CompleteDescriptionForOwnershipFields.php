<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Adds human-readable descriptions for the ownership fields (e.g. owner, organization) of the entity.
 */
class CompleteDescriptionForOwnershipFields implements ProcessorInterface
{
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
            // ownership fields are available only for configurable entities
            return;
        }

        $definition = $context->getResult();
        $entityConfig = $this->entityConfigProvider->getConfig($entityClass);
        $this->updateOwnershipFieldDescription(
            $definition,
            $entityConfig,
            'owner_field_name',
            'An Owner record represents the ownership capabilities of the record'
        );
        $this->updateOwnershipFieldDescription(
            $definition,
            $entityConfig,
            'organization_field_name',
            'An Organization record represents a real enterprise, business, firm, '
            . 'company or another organization, to which the record belongs'
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ConfigInterface        $entityConfig
     * @param string                 $configKey
     * @param string                 $description
     */
    protected function updateOwnershipFieldDescription(
        EntityDefinitionConfig $definition,
        ConfigInterface $entityConfig,
        $configKey,
        $description
    ) {
        $propertyPath = $entityConfig->get($configKey);
        if ($propertyPath) {
            $field = $definition->findField($propertyPath, true);
            if (null !== $field) {
                $existingDescription = $field->getDescription();
                if (empty($existingDescription)) {
                    $field->setDescription($description);
                }
            }
        }
    }
}
