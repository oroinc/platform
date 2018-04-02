<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The abstract class for form types are used to work with entity config attributes.
 * You can use this form type if you need to disable changing of an attribute value
 * in case if there is 'immutable' attribute set to true in the same config scope as your attribute.
 */
abstract class AbstractConfigType extends AbstractType
{
    /** @var ConfigTypeHelper */
    private $typeHelper;

    /**
     * @param ConfigTypeHelper $typeHelper
     */
    public function __construct(ConfigTypeHelper $typeHelper)
    {
        $this->typeHelper = $typeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer(
            'disabled',
            function (Options $options, $value) {
                return $this->isReadOnly($options) ? true : $value;
            }
        )
        ->setNormalizer(
            'validation_groups',
            function (Options $options, $value) {
                return $options['disabled'] ? false : $value;
            }
        );
    }

    /**
     * Checks if the form type should be read-only or not
     *
     * @param Options $options
     *
     * @return bool
     */
    protected function isReadOnly(Options $options)
    {
        /** @var ConfigIdInterface $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (empty($className)) {
            return false;
        }

        return $this->typeHelper->isImmutable(
            $configId->getScope(),
            $className,
            $this->typeHelper->getFieldName($configId)
        );
    }
}
