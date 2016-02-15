<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeDataItemContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeDataItemProcessor;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SetDataItemCustomizationHandler implements ProcessorInterface
{
    /** @var CustomizeDataItemProcessor */
    protected $customizationProcessor;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param CustomizeDataItemProcessor $customizationProcessor
     * @param DoctrineHelper             $doctrineHelper
     */
    public function __construct(
        CustomizeDataItemProcessor $customizationProcessor,
        DoctrineHelper $doctrineHelper
    ) {
        $this->customizationProcessor = $customizationProcessor;
        $this->doctrineHelper         = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (empty($definition)) {
            // nothing to update
            return;
        }

        $this->setCustomizationHandler($definition, $context);
        $context->setResult($definition);
    }

    /**
     * @param array         $definition
     * @param ConfigContext $context
     */
    protected function setCustomizationHandler(array &$definition, ConfigContext $context)
    {
        $entityClass = $context->getClassName();

        $definition[ConfigUtil::POST_SERIALIZE] = $this->getRootCustomizationHandler(
            $context,
            $entityClass,
            isset($definition[ConfigUtil::POST_SERIALIZE]) ? $definition[ConfigUtil::POST_SERIALIZE] : null
        );

        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // we can set customization handlers for associations only for manageable entity,
            // because for other types of entities we do not have metadata
            return;
        }

        if (isset($definition[ConfigUtil::FIELDS]) && is_array($definition[ConfigUtil::FIELDS])) {
            $this->processFields(
                $context,
                $definition[ConfigUtil::FIELDS],
                $entityClass,
                $this->doctrineHelper->getEntityMetadataForClass($entityClass)
            );
        }
    }

    /**
     * @param ConfigContext $context
     * @param array         $fields
     * @param string        $rootEntityClass
     * @param ClassMetadata $metadata
     * @param string|null   $fieldPath
     */
    protected function processFields(
        ConfigContext $context,
        array &$fields,
        $rootEntityClass,
        ClassMetadata $metadata,
        $fieldPath = null
    ) {
        foreach ($fields as $fieldName => &$fieldConfig) {
            if (is_array($fieldConfig)) {
                $propertyPath = !empty($fieldConfig[ConfigUtil::PROPERTY_PATH])
                    ? $fieldConfig[ConfigUtil::PROPERTY_PATH]
                    : $fieldName;
                $path         = ConfigUtil::explodePropertyPath($propertyPath);
                if (count($path) === 1) {
                    $this->setFieldCustomizationHandler(
                        $context,
                        $fieldConfig,
                        $metadata,
                        $propertyPath,
                        $rootEntityClass,
                        $this->buildFieldPath($fieldName, $fieldPath)
                    );
                } else {
                    $linkedField    = array_pop($path);
                    $linkedMetadata = $this->doctrineHelper->findEntityMetadataByPath($metadata->name, $path);
                    if (null !== $linkedMetadata) {
                        $this->setFieldCustomizationHandler(
                            $context,
                            $fieldConfig,
                            $linkedMetadata,
                            $linkedField,
                            $rootEntityClass,
                            $this->buildFieldPath($fieldName, $fieldPath)
                        );
                    }
                }
            }
        }
    }

    /**
     * @param ConfigContext $context
     * @param array         $fieldConfig
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     * @param string        $rootEntityClass
     * @param string        $fieldPath
     */
    protected function setFieldCustomizationHandler(
        ConfigContext $context,
        array &$fieldConfig,
        ClassMetadata $metadata,
        $fieldName,
        $rootEntityClass,
        $fieldPath
    ) {
        if (isset($fieldConfig[ConfigUtil::FIELDS])
            && is_array($fieldConfig[ConfigUtil::FIELDS])
            && $metadata->hasAssociation($fieldName)
        ) {
            $fieldConfig[ConfigUtil::POST_SERIALIZE] = $this->getCustomizationHandler(
                $context,
                $rootEntityClass,
                $fieldPath,
                $metadata->getAssociationTargetClass($fieldName),
                isset($fieldConfig[ConfigUtil::POST_SERIALIZE]) ? $fieldConfig[ConfigUtil::POST_SERIALIZE] : null
            );
            $this->processFields(
                $context,
                $fieldConfig[ConfigUtil::FIELDS],
                $rootEntityClass,
                $metadata,
                $fieldPath
            );
        }
    }

    /**
     * @param string      $fieldName
     * @param string|null $parentFieldPath
     *
     * @return string
     */
    protected function buildFieldPath($fieldName, $parentFieldPath = null)
    {
        return null !== $parentFieldPath
            ? $parentFieldPath . ConfigUtil::PATH_DELIMITER . $fieldName
            : $fieldName;
    }

    /**
     * @param ConfigContext $context
     *
     * @return CustomizeDataItemContext
     */
    protected function createCustomizationContext(ConfigContext $context)
    {
        /** @var CustomizeDataItemContext $customizationContext */
        $customizationContext = $this->customizationProcessor->createContext();
        $customizationContext->setVersion($context->getVersion());
        $customizationContext->setRequestType($context->getRequestType());

        return $customizationContext;
    }

    /**
     * @param ConfigContext $context
     * @param string        $entityClass
     * @param callable|null $previousHandler
     *
     * @return callable
     */
    protected function getRootCustomizationHandler(
        ConfigContext $context,
        $entityClass,
        $previousHandler
    ) {
        return function (array $item) use ($context, $entityClass, $previousHandler) {
            if (null !== $previousHandler) {
                $item = call_user_func($previousHandler, $item);
            }

            $customizationContext = $this->createCustomizationContext($context);
            $customizationContext->setClassName($entityClass);
            $customizationContext->setResult($item);
            $this->customizationProcessor->process($customizationContext);

            return $customizationContext->getResult();
        };
    }

    /**
     * @param ConfigContext $context
     * @param string        $rootEntityClass
     * @param string        $propertyPath
     * @param string        $entityClass
     * @param callable|null $previousHandler
     *
     * @return callable
     */
    protected function getCustomizationHandler(
        ConfigContext $context,
        $rootEntityClass,
        $propertyPath,
        $entityClass,
        $previousHandler
    ) {
        return function (array $item) use ($context, $rootEntityClass, $propertyPath, $entityClass, $previousHandler) {
            if (null !== $previousHandler) {
                $item = call_user_func($previousHandler, $item);
            }

            $customizationContext = $this->createCustomizationContext($context);
            $customizationContext->setRootClassName($rootEntityClass);
            $customizationContext->setPropertyPath($propertyPath);
            $customizationContext->setClassName($entityClass);
            $customizationContext->setResult($item);
            $this->customizationProcessor->process($customizationContext);

            return $customizationContext->getResult();
        };
    }
}
