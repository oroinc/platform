<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setNormalizers(
            [
                'disabled'          => function (Options $options, $value) {
                    return $this->isReadOnly($options) ? true : $value;
                },
                'validation_groups' => function (Options $options, $value) {
                    return $options['disabled'] ? false : $value;
                }
            ]
        );
    }

    /**
     * Checks if the form type should be read-only or not
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
            // check 'immutable' attribute
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
}
