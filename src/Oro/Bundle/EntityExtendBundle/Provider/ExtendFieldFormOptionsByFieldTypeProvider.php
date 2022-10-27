<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * Returns basic form options specific for field type.
 */
class ExtendFieldFormOptionsByFieldTypeProvider implements ExtendFieldFormOptionsProviderInterface
{
    private EntityConfigManager $entityConfigManager;

    private ExtendFieldFormTypeProvider $extendFieldFormTypeProvider;

    public function __construct(
        EntityConfigManager $entityConfigManager,
        ExtendFieldFormTypeProvider $extendFieldFormTypeProvider
    ) {
        $this->entityConfigManager = $entityConfigManager;
        $this->extendFieldFormTypeProvider = $extendFieldFormTypeProvider;
    }

    public function getOptions(string $className, string $fieldName): array
    {
        $className = ClassUtils::getRealClass($className);
        $formFieldConfig = $this->entityConfigManager->getFieldConfig('form', $className, $fieldName);
        /** @var FieldConfigId $formFieldConfigId */
        $formFieldConfigId = $formFieldConfig->getId();

        $defaultFormOptions = $this->getDefaultOptions($formFieldConfigId);
        $formOptions = $this->extendFieldFormTypeProvider->getFormOptions($formFieldConfigId->getFieldType());

        return array_replace_recursive($defaultFormOptions, $formOptions);
    }

    private function getDefaultOptions(FieldConfigId $fieldConfigId): array
    {
        $className = $fieldConfigId->getClassName();
        $fieldName = $fieldConfigId->getFieldName();
        $options = [];

        switch ($fieldConfigId->getFieldType()) {
            case 'boolean':
                // Doctrine DBAL can't save null to boolean field
                // see https://github.com/doctrine/dbal/issues/2580
                $options['configs']['allowClear'] = false;
                $options['choices'] = [
                    'No' => false,
                    'Yes' => true,
                ];
                break;
            case 'float':
            case 'decimal':
                $options['grouping'] = true;
                break;
            case 'enum':
                $options['enum_code'] = $this->entityConfigManager->getFieldConfig('enum', $className, $fieldName)
                    ->get('enum_code');
                break;
            case 'multiEnum':
                $options['expanded'] = true;
                $options['enum_code'] = $this->entityConfigManager->getFieldConfig('enum', $className, $fieldName)
                    ->get('enum_code');
                break;
            case RelationType::MANY_TO_ONE:
                $options = $this->getDefaultOptionsForToOne($className, $fieldName);
                break;
            case RelationType::ONE_TO_MANY:
            case RelationType::MANY_TO_MANY:
                $options = $this->getDefaultOptionsForToMany($className, $fieldName);
                break;
        }

        return $options;
    }

    private function getDefaultOptionsForToMany(string $className, string $fieldName): array
    {
        $extendFieldConfig = $this->entityConfigManager->getFieldConfig('extend', $className, $fieldName);

        $classArray = explode('\\', $extendFieldConfig->get('target_entity'));
        $blockName = array_pop($classArray);

        $options['block'] = $blockName;
        $options['block_config'] = [
            $blockName => ['title' => null, 'subblocks' => [['useSpan' => false]]],
        ];
        $options['class'] = $extendFieldConfig->get('target_entity');
        $options['selector_window_title'] = 'Select ' . $blockName;
        $options['initial_elements'] = null;
        if (!$extendFieldConfig->is('without_default')) {
            $options['default_element'] = ExtendConfigDumper::DEFAULT_PREFIX . $fieldName;
        }

        return $options;
    }

    private function getDefaultOptionsForToOne(string $className, string $fieldName): array
    {
        $extendFieldConfig = $this->entityConfigManager->getFieldConfig('extend', $className, $fieldName);

        $options['entity_class'] = $extendFieldConfig->get('target_entity');
        $options['configs'] = [
            'placeholder' => 'oro.form.choose_value',
            'component' => 'relation',
            'target_entity' => str_replace('\\', '_', $extendFieldConfig->get('target_entity')),
            'target_field' => $extendFieldConfig->get('target_field'),
            'properties' => [$extendFieldConfig->get('target_field')],
        ];

        return $options;
    }
}
