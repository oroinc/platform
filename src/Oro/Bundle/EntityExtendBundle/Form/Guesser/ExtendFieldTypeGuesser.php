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
    private ConfigProvider $formConfigProvider;

    private ConfigProvider $extendConfigProvider;

    private ExtendFieldFormTypeProvider $extendFieldFormTypeProvider;

    private ExtendFieldFormOptionsProviderInterface $extendFieldFormOptionsProvider;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $formConfigProvider,
        ConfigProvider $extendConfigProvider,
        ExtendFieldFormTypeProvider $extendFieldFormTypeProvider,
        ExtendFieldFormOptionsProviderInterface $extendFieldFormOptionsProvider
    ) {
        parent::__construct($managerRegistry, $entityConfigProvider);

        $this->formConfigProvider = $formConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->extendFieldFormTypeProvider = $extendFieldFormTypeProvider;
        $this->extendFieldFormOptionsProvider = $extendFieldFormOptionsProvider;
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

        $options = $this->extendFieldFormOptionsProvider->getOptions($className, $fieldName);

        return $this->createTypeGuess($type, $options);
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
