<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Guesser;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Form\Guesser\AbstractFormGuesser;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProviderInterface;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormTypeProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides a guess for form type and form options based on entity field config.
 */
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

    /** @var ExtendFieldFormTypeProvider */
    private $extendFieldFormTypeProvider;

    /** @var ExtendFieldFormOptionsProviderInterface */
    private $extendFieldFormOptionsProvider;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $formConfigProvider,
        ConfigProvider $extendConfigProvider,
        ConfigProvider $enumConfigProvider
    ) {
        parent::__construct($managerRegistry, $entityConfigProvider);

        $this->formConfigProvider = $formConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
    }

    public function setExtendFieldFormTypeProvider(ExtendFieldFormTypeProvider $extendFieldFormTypeProvider): void
    {
        $this->extendFieldFormTypeProvider = $extendFieldFormTypeProvider;
    }

    public function setExtendFieldFormOptionsProvider(
        ExtendFieldFormOptionsProviderInterface $extendFieldFormOptionsProvider
    ): void {
        $this->extendFieldFormOptionsProvider = $extendFieldFormOptionsProvider;
    }

    /**
     * @param string $extendType
     * @param string $formType
     * @param array  $formOptions
     */
    public function addExtendTypeMapping($extendType, $formType, array $formOptions = [])
    {
        if ($this->extendFieldFormTypeProvider instanceof ExtendFieldFormTypeProvider) {
            $this->extendFieldFormTypeProvider->addExtendTypeMapping($extendType, $formType, $formOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($className, $property)
    {
        if (!$this->extendConfigProvider->hasConfig($className, $property)) {
            return $this->createDefaultTypeGuess();
        }

        $formFieldConfig = $this->formConfigProvider->getConfig($className, $property);
        if (!$formFieldConfig->is('is_enabled')) {
            return $this->createDefaultTypeGuess();
        }

        /** @var FieldConfigId $formFieldConfigId */
        $formFieldConfigId = $formFieldConfig->getId();
        $fieldName = $formFieldConfigId->getFieldName();
        $fieldType = $formFieldConfigId->getFieldType();

        if ($formFieldConfig->has('type')) {
            $type = $formFieldConfig->get('type');
        } else {
            $type = $this->extendFieldFormTypeProvider->getFormType($fieldType);
        }

        /** @var FieldConfigId $extendFieldConfig */
        $extendFieldConfig  = $this->extendConfigProvider->getConfig($className, $fieldName);
        if ($type === '' || !$this->isApplicableField($extendFieldConfig)) {
            return $this->createDefaultTypeGuess();
        }

        $options = $this->extendFieldFormOptionsProvider instanceof ExtendFieldFormOptionsProviderInterface
            ? $this->extendFieldFormOptionsProvider->getOptions($className, $fieldName)
            : [];

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
        return [];
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
