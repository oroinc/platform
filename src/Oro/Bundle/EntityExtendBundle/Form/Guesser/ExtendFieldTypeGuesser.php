<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Guesser;

use Symfony\Component\Form\Guess;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\Form\Guesser\AbstractFormGuesser;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal;

use Symfony\Component\Validator\Constraints\Length;

class ExtendFieldTypeGuesser extends AbstractFormGuesser
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ConfigProvider */
    protected $formConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ConfigProvider */
    protected $enumConfigProvider;

    /** @var array */
    protected $typeMap = [];

    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $formConfigProvider,
        ConfigProvider $extendConfigProvider,
        ConfigProvider $enumConfigProvider
    ) {
        $this->formConfigProvider   = $formConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->managerRegistry      = $managerRegistry;
        $this->enumConfigProvider   = $enumConfigProvider;
    }

    /**
     * @param string $extendType
     * @param string $formType
     * @param array  $formOptions
     */
    public function addExtendTypeMapping($extendType, $formType, array $formOptions = [])
    {
        $this->typeMap[$extendType] = ['type' => $formType, 'options' => $formOptions];
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($className, $property)
    {
        if (!$this->extendConfigProvider->hasConfig($className, $property)) {
            return $this->createDefaultTypeGuess();
        }

        $formConfig = $this->formConfigProvider->getConfig($className, $property);
        if (!$formConfig->is('is_enabled')) {
            return $this->createDefaultTypeGuess();
        }

        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $formConfig->getId();
        $fieldName     = $fieldConfigId->getFieldName();
        $extendConfig  = $this->extendConfigProvider->getConfig($className, $fieldName);

        if ($formConfig->has('type')) {
            $isTypeNotExists = false;
            $type = $formConfig->get('type');
        } else {
            $isTypeNotExists = empty($this->typeMap[$fieldConfigId->getFieldType()]);
            $type = $this->typeMap[$fieldConfigId->getFieldType()]['type'];
        }

        $options = $this->getOptions($extendConfig, $fieldConfigId);
        $options = $this->addConstraintsToOptions($options, $extendConfig, $fieldConfigId);
        if (!$this->isApplicableField($extendConfig) || $isTypeNotExists) {
            return $this->createDefaultTypeGuess();
        }

        $entityConfig = $this->entityConfigProvider->getConfig($className, $fieldName);

        $options      = array_replace_recursive(
            [
                'label'    => $entityConfig->get('label'),
                'required' => false,
                'block'    => 'general',
            ],
            $options,
            $this->typeMap[$fieldConfigId->getFieldType()]['options']
        );

        return $this->createTypeGuess($type, $options);
    }

    /**
     * @param ConfigInterface $extendConfig
     * @param FieldConfigId   $fieldConfigId
     *
     * @return array
     */
    protected function getOptions(ConfigInterface $extendConfig, FieldConfigId $fieldConfigId)
    {
        $className = $fieldConfigId->getClassName();
        $fieldName = $fieldConfigId->getFieldName();

        $options = [];

        switch ($fieldConfigId->getFieldType()) {
            case 'boolean':
                $options['empty_value'] = false;
                $options['choices'] = ['No', 'Yes'];
                break;
            case 'enum':
                $options['enum_code'] = $this->enumConfigProvider->getConfig($className, $fieldName)
                    ->get('enum_code');
                break;
            case 'multiEnum':
                $options['expanded']  = true;
                $options['enum_code'] = $this->enumConfigProvider->getConfig($className, $fieldName)
                    ->get('enum_code');
                break;
            case RelationType::MANY_TO_ONE:
                $options['entity_class'] = $extendConfig->get('target_entity');
                $options['configs']      = [
                    'placeholder'   => 'oro.form.choose_value',
                    'component'  => 'relation',
                    'target_entity' => str_replace('\\', '_', $extendConfig->get('target_entity')),
                    'target_field'  => $extendConfig->get('target_field'),
                    'properties'    => [$extendConfig->get('target_field')],
                ];
                break;
            case RelationType::ONE_TO_MANY:
            case RelationType::MANY_TO_MANY:
                $classArray = explode('\\', $extendConfig->get('target_entity'));
                $blockName  = array_pop($classArray);

                $options['block']                 = $blockName;
                $options['block_config']          = [
                    $blockName => ['title' => null, 'subblocks' => [['useSpan' => false]]]
                ];
                $options['class']                 = $extendConfig->get('target_entity');
                $options['selector_window_title'] = 'Select ' . $blockName;
                $options['initial_elements']      = null;
                if (!$extendConfig->is('without_default')) {
                    $options['default_element'] = ExtendConfigDumper::DEFAULT_PREFIX . $fieldName;
                }
                break;
        }

        return $options;
    }

    /**
     * @param array $options
     * @param ConfigInterface $extendConfig
     * @param FieldConfigId $fieldConfigId
     *
     * @return array
     */
    protected function addConstraintsToOptions(
        array $options,
        ConfigInterface $extendConfig,
        FieldConfigId $fieldConfigId
    ) {
        switch ($fieldConfigId->getFieldType()) {
            case 'decimal':
                $options['constraints'] = [new Decimal([
                    'precision' => $extendConfig->get('precision'),
                    'scale'     => $extendConfig->get('scale')
                ])];
                break;
            case 'string':
                $length = $extendConfig->get('length') ?: 255;
                $options['constraints'] = [new Length([
                    'max' => $length
                ])];
                break;
        }

        return $options;
    }

    /**
     * @param ConfigInterface $extendConfig
     *
     * @return bool
     */
    protected function isApplicableField(ConfigInterface $extendConfig)
    {
        return
            $extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
            && ExtendHelper::isFieldAccessible($extendConfig)
            && !in_array($extendConfig->getId()->getFieldType(), RelationType::$toAnyRelations, true)
            && (
                !$extendConfig->has('target_entity')
                || ExtendHelper::isEntityAccessible(
                    $this->extendConfigProvider->getConfig($extendConfig->get('target_entity'))
                )
            );
    }
}
