<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumPublicType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($this->isReadOnly($options)) {
            $view->vars['disabled'] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isReadOnly($options)
    {
        $configId = $options['config_id'];
        if (!($configId instanceof FieldConfigId)) {
            return false;
        }

        $className = $configId->getClassName();
        if (empty($className)) {
            return false;
        }

        $fieldName = $configId->getFieldName();

        // disable for system fields
        $extendConfigProvider = $this->configManager->getProvider('extend');
        if ($extendConfigProvider->hasConfig($className, $fieldName)) {
            $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);
            if ($extendConfig->is('owner', ExtendScope::OWNER_SYSTEM)) {
                return true;
            }
        }

        // disable for immutable enums
        $enumConfigProvider = $this->configManager->getProvider('enum');
        if ($enumConfigProvider->hasConfig($className, $fieldName)) {
            $enumFieldConfig = $enumConfigProvider->getConfig($className, $fieldName);
            $enumCode        = $enumFieldConfig->get('enum_code');
            if (!empty($enumCode)) {
                $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
                if ($enumConfigProvider->hasConfig($enumValueClassName)) {
                    $enumConfig = $enumConfigProvider->getConfig($enumValueClassName);
                    if ($enumConfig->get('immutable')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_enum_public';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
