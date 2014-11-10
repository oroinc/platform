<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Guesser;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ExtendFieldTypeGuesser implements FormTypeGuesserInterface
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ConfigProvider */
    protected $formConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ConfigManager */
    protected $configManager;

    /** @var array */
    protected $typeMap = [];

    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigManager $configManager,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $formConfigProvider,
        ConfigProvider $extendConfigProvider
    ) {
        $this->formConfigProvider   = $formConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->managerRegistry      = $managerRegistry;
        $this->configManager        = $configManager;
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
        $formConfig = $this->formConfigProvider->getConfig($className, $property);
        if (!$formConfig->is('is_enabled')) {
            return new ValueGuess(false, ValueGuess::LOW_CONFIDENCE);
        }

        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $formConfig->getId();
        $fieldName     = $fieldConfigId->getFieldName();
        $extendConfig  = $this->extendConfigProvider->getConfig($className, $fieldName);

        $isTypeNotExists = empty($this->typeMap[$fieldConfigId->getFieldType()]);
        $options         = $this->getOptions($extendConfig, $fieldConfigId);

        if (!$this->isApplicableField($extendConfig) || $isTypeNotExists || empty($options)) {
            return new ValueGuess(false, ValueGuess::LOW_CONFIDENCE);
        }

        $entityConfig = $this->entityConfigProvider->getConfig($className, $fieldName);
        $type         = $this->typeMap[$fieldConfigId->getFieldType()]['type'];
        $options      = array_replace_recursive(
            [
                'label'    => $entityConfig->get('label'),
                'required' => false,
                'block'    => 'general',
            ],
            $options,
            $this->typeMap[$fieldConfigId->getFieldType()]['options']
        );

        return new TypeGuess($type, $options, TypeGuess::HIGH_CONFIDENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function guessRequired($class, $property)
    {
        return new ValueGuess(false, ValueGuess::LOW_CONFIDENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function guessMaxLength($class, $property)
    {
        return new ValueGuess(null, ValueGuess::LOW_CONFIDENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function guessPattern($class, $property)
    {
        return new ValueGuess(null, ValueGuess::LOW_CONFIDENCE);
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
            case 'optionSet':
                $options['entityClassName'] = $className;
                $options['entityFieldName'] = $fieldName;
                break;
            case 'enum':
                $options['enum_code'] = $this->configManager->getProvider('enum')
                    ->getConfig($className, $fieldName)
                    ->get('enum_code');
                break;
            case 'multiEnum':
                $options['expanded']  = true;
                $options['enum_code'] = $this->configManager->getProvider('enum')
                    ->getConfig($className, $fieldName)
                    ->get('enum_code');
                break;
            case 'manyToOne':
                $options['entity_class'] = $extendConfig->get('target_entity');
                $options['configs']      = [
                    'placeholder'   => 'oro.form.choose_value',
                    'extra_config'  => 'relation',
                    'target_entity' => str_replace('\\', '_', $extendConfig->get('target_entity')),
                    'target_field'  => $extendConfig->get('target_field'),
                    'properties'    => [$extendConfig->get('target_field')],
                ];
                break;
            case 'oneToMany':
            case 'manyToMany':
                $classArray = explode('\\', $extendConfig->get('target_entity'));
                $blockName  = array_pop($classArray);

                $options['block']                 = $blockName;
                $options['block_config']          = [
                    $blockName => ['title' => null, 'subblocks' => [['useSpan' => false]]]
                ];
                $options['class']                 = $extendConfig->get('target_entity');
                $options['selector_window_title'] = 'Select ' . $blockName;
                $options['initial_elements']      = null;
                $options['mapped']                = false;
                $options['extend']                = true;
                if (!$extendConfig->is('without_default')) {
                    $options['default_element'] = ExtendConfigDumper::DEFAULT_PREFIX . $fieldName;
                }
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
            !$extendConfig->is('is_deleted')
            && $extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
            && !$extendConfig->is('state', ExtendScope::STATE_NEW)
            && !in_array($extendConfig->getId()->getFieldType(), ['ref-one', 'ref-many'])
            && (
                !$extendConfig->has('target_entity')
                || !$this->extendConfigProvider->getConfig($extendConfig->get('target_entity'))->is('is_deleted')
            );
    }
}
