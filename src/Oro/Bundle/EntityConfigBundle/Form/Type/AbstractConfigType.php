<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * The abstract class for form types are used to work with entity config attributes.
 * You can use this form type if you need to disable changing of an attribute value
 * in case if there is 'immutable' attribute set to true in the same config scope as your attribute.
 */
abstract class AbstractConfigType extends AbstractType
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
            $this->disableView($view);
        }
    }

    /**
     * Check if the form view should be disabled or not
     *
     * @param array $options
     *
     * @return bool
     */
    protected function isReadOnly($options)
    {
        /** @var ConfigIdInterface $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();
        $fieldName = $configId instanceof FieldConfigId ? $configId->getFieldName() : null;

        if (!empty($className)) {
            // disable for immutable entities or fields
            $configProvider = $this->configManager->getProvider($configId->getScope());
            if ($configProvider->hasConfig($className, $fieldName)) {
                $immutable = $configProvider->getConfig($className, $fieldName)->get('immutable');
                if (true === $immutable) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Disables the form view
     *
     * @param FormView $view
     */
    protected function disableView(FormView $view)
    {
        $view->vars['disabled'] = true;
    }

    /**
     * @param array  $vars
     * @param string $cssClass
     */
    protected function appendClassAttr(array &$vars, $cssClass)
    {
        if (isset($vars['attr']['class'])) {
            $vars['attr']['class'] .= ' ' . $cssClass;
        } else {
            $vars['attr']['class'] = $cssClass;
        }
    }
}
