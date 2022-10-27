<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator checks that non-filterable/sortable/searchable attributes are not with enabled such configurations.
 */
class AttributeConfigurationValidator extends ConstraintValidator
{
    const ALIAS = 'oro_entity_extend.validator.attribute_configuration';

    /**
     * @var AttributeTypeRegistry
     */
    protected $attributeTypeRegistry;

    /**
     * @var ConfigProvider
     */
    protected $attributeConfigProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(
        AttributeTypeRegistry $attributeTypeRegistry,
        ConfigProvider $attributeConfigProvider,
        ConfigManager $configManager
    ) {
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->attributeConfigProvider = $attributeConfigProvider;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof FieldConfigModel) {
            return;
        }

        $className = $value->getEntity()->getClassName();
        $hasAttributes = $this->attributeConfigProvider->getConfig($className)->is('has_attributes');
        $attributeScopeData = $this->configManager->createFieldConfigByModel($value, 'attribute');
        if (!$hasAttributes || !$attributeScopeData->is('is_attribute')) {
            return;
        }

        $attributeType = $this->attributeTypeRegistry->getAttributeType($value);
        if ($attributeScopeData->is('filterable') && !$attributeType->isFilterable($value)) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ type }}' => $value->getType(), '{{ option }}' => 'filterable']
            );
        }

        if ($attributeScopeData->is('sortable') && !$attributeType->isSortable($value)) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ type }}' => $value->getType(), '{{ option }}' => 'sortable']
            );
        }

        if ($attributeScopeData->is('searchable') && !$attributeType->isSearchable($value)) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ type }}' => $value->getType(), '{{ option }}' => 'searchable']
            );
        }
    }
}
