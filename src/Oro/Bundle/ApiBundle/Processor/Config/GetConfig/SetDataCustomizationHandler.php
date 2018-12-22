<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
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
    /** @var ActionProcessorInterface */
    private $customizationProcessor;

    /**
     * @param ActionProcessorInterface $customizationProcessor
     */
    public function __construct(ActionProcessorInterface $customizationProcessor)
    {
        $this->customizationProcessor = $customizationProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $this->setCustomizationHandler($definition, $context);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ConfigContext          $context
     */
    private function setCustomizationHandler(EntityDefinitionConfig $definition, ConfigContext $context)
    {
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $entityClass = $context->getClassName();
        $definition->setPostSerializeHandler(
            new EntityHandler(
                $this->customizationProcessor,
                $version,
                $requestType,
                $entityClass,
                $definition,
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
                true,
                $definition->getPostSerializeCollectionHandler()
            )
        );

        $this->processAssociations($context, $definition, $entityClass);
    }

    /**
     * @param ConfigContext          $context
     * @param EntityDefinitionConfig $definition
     * @param string                 $rootEntityClass
     * @param string|null            $fieldPath
     */
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

    /**
     * @param EntityDefinitionFieldConfig $field
     *
     * @return bool
     */
    private function isCustomizableAssociation(EntityDefinitionFieldConfig $field): bool
    {
        return
            $field->hasTargetEntity()
            && $field->getTargetClass()
            && !DataType::isAssociationAsField($field->getDataType());
    }

    /**
     * @param ConfigContext               $context
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $rootEntityClass
     * @param string                      $fieldPath
     */
    private function setAssociationCustomizationHandler(
        ConfigContext $context,
        EntityDefinitionFieldConfig $field,
        string $rootEntityClass,
        string $fieldPath
    ): void {
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $definition = $context->getResult();
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
                true,
                $targetEntity->getPostSerializeCollectionHandler()
            )
        );
        $this->processAssociations($context, $targetEntity, $rootEntityClass, $fieldPath);
    }

    /**
     * @param string      $fieldName
     * @param string|null $parentFieldPath
     *
     * @return string
     */
    private function buildFieldPath(string $fieldName, ?string $parentFieldPath = null): string
    {
        return null !== $parentFieldPath
            ? $parentFieldPath . ConfigUtil::PATH_DELIMITER . $fieldName
            : $fieldName;
    }
}
