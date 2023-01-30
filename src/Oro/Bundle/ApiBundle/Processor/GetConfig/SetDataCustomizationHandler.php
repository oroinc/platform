<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\AssociationHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\EntityHandler;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Registers a post loading handler for the entity and all associated entities.
 * It allows to customize loaded data by registering own processors for the "customize_loaded_data" action.
 */
class SetDataCustomizationHandler implements ProcessorInterface
{
    private ActionProcessorInterface $customizationProcessor;

    public function __construct(ActionProcessorInterface $customizationProcessor)
    {
        $this->customizationProcessor = $customizationProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $this->setCustomizationHandler($definition, $context);
    }

    private function setCustomizationHandler(EntityDefinitionConfig $definition, ConfigContext $context): void
    {
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $entityClass = $context->getClassName();
        $configExtras = $context->getExtras();
        $definition->setPostSerializeHandler(
            new EntityHandler(
                $this->customizationProcessor,
                $version,
                $requestType,
                $entityClass,
                $definition,
                $configExtras,
                false,
                $definition->getPostSerializeHandler()
            )
        );
        $definition->setPostSerializeCollectionHandler(
            new EntityHandler(
                $this->customizationProcessor,
                $version,
                $requestType,
                $entityClass,
                $definition,
                $configExtras,
                true,
                $definition->getPostSerializeCollectionHandler()
            )
        );

        $this->processAssociations($context, $definition, $entityClass);
    }

    private function processAssociations(
        ConfigContext $context,
        EntityDefinitionConfig $definition,
        string $rootEntityClass,
        ?string $fieldPath = null
    ): void {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($this->isCustomizableAssociation($field)) {
                $this->setAssociationCustomizationHandler(
                    $context,
                    $field,
                    $rootEntityClass,
                    $this->buildFieldPath($fieldName, $fieldPath)
                );
            }
        }
    }

    private function isCustomizableAssociation(EntityDefinitionFieldConfig $field): bool
    {
        return
            $field->hasTargetEntity()
            && $field->getTargetClass()
            && !DataType::isAssociationAsField($field->getDataType());
    }

    private function setAssociationCustomizationHandler(
        ConfigContext $context,
        EntityDefinitionFieldConfig $field,
        string $rootEntityClass,
        string $fieldPath
    ): void {
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $definition = $context->getResult();
        $configExtras = $context->getExtras();
        /** @var EntityDefinitionConfig $targetEntity */
        $targetEntity = $field->getTargetEntity();
        $targetEntityClass = $field->getTargetClass();
        $targetEntity->setPostSerializeHandler(
            new AssociationHandler(
                $this->customizationProcessor,
                $version,
                $requestType,
                $rootEntityClass,
                $fieldPath,
                $targetEntityClass,
                $definition,
                $configExtras,
                false,
                $targetEntity->getPostSerializeHandler()
            )
        );
        $targetEntity->setPostSerializeCollectionHandler(
            new AssociationHandler(
                $this->customizationProcessor,
                $version,
                $requestType,
                $rootEntityClass,
                $fieldPath,
                $targetEntityClass,
                $definition,
                $configExtras,
                true,
                $targetEntity->getPostSerializeCollectionHandler()
            )
        );
        $this->processAssociations($context, $targetEntity, $rootEntityClass, $fieldPath);
    }

    private function buildFieldPath(string $fieldName, ?string $parentFieldPath = null): string
    {
        return null !== $parentFieldPath
            ? $parentFieldPath . ConfigUtil::PATH_DELIMITER . $fieldName
            : $fieldName;
    }
}
