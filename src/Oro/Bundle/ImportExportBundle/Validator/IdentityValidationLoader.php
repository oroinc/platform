<?php

namespace Oro\Bundle\ImportExportBundle\Validator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Validator\AbstractFieldConfigBasedValidationLoader;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Loads validation metadata for identity fields of entities
 */
class IdentityValidationLoader extends AbstractFieldConfigBasedValidationLoader
{
    /** @var string */
    public const IMPORT_IDENTITY_FIELDS_VALIDATION_GROUP = 'import_identity';

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    private $cachedEntitiesIdentifierFieldNames = [];

    /**
     * @param ConfigProvider $extendConfigProvider
     * @param ConfigProvider $fieldConfigProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ConfigProvider $extendConfigProvider,
        ConfigProvider $fieldConfigProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->fieldConfigProvider = $fieldConfigProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    protected function processFieldConfig(ClassMetadata $metadata, ConfigInterface $fieldConfig): void
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $fieldConfig->getId();
        $fieldName = $fieldConfigId->getFieldName();
        $className = $metadata->getClassName();

        $entityIdentifierFieldName = $this->getSingleEntityIdentifierFieldName($className);
        if ($fieldName !== $entityIdentifierFieldName && !$fieldConfig->is('identity')) {
            return;
        }

        if (!$this->isApplicable($className, $fieldName)) {
            return;
        }

        $constraints = $this->getConstraintsByFieldType($fieldConfigId->getFieldType());
        foreach ($constraints as $constraint) {
            $metadata->addPropertyConstraint($fieldName, $constraint);
        }
    }

    /**
     * Check if field applicable to add constraint
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isApplicable($className, $fieldName)
    {
        $extendConfig = $this->extendConfigProvider->getConfig($className, $fieldName);

        return !$extendConfig->is('is_deleted') &&
            $extendConfig->is('state', ExtendScope::STATE_ACTIVE);
    }

    /**
     * @param string $className
     *
     * @return null|string
     */
    protected function getSingleEntityIdentifierFieldName(string $className): ?string
    {
        if (!isset($this->cachedEntitiesIdentifierFieldNames[$className])) {
            $this->cachedEntitiesIdentifierFieldNames[$className] =
                $this->doctrineHelper->getSingleEntityIdentifierFieldName(
                    $className,
                    false
                );
        }

        return $this->cachedEntitiesIdentifierFieldNames[$className];
    }
}
