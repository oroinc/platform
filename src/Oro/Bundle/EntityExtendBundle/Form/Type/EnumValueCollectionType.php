<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueCollectionType extends AbstractType
{
    /** @var EnumTypeHelper */
    protected $typeHelper;

    /**
     * @param EnumTypeHelper $typeHelper
     */
    public function __construct(EnumTypeHelper $typeHelper)
    {
        $this->typeHelper = $typeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'ignore_primary_behaviour' => true,
                'type'                     => 'oro_entity_extend_enum_value'
            ]
        );

        $resolver->setNormalizers(
            [
                'can_add_and_delete' => function (Options $options, $value) {
                    return $this->getState($options) > 0 ? false : $value;
                },
                'disabled' => function (Options $options, $value) {
                    return $this->getState($options) === 2 ? true : $value;
                },
                'validation_groups' => function (Options $options, $value) {
                    return $options['disabled'] ? false : $value;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['multiple'] = $this->typeHelper->getFieldType($options['config_id']) === 'multiEnum';
    }

    /**
     * Checks if the form type should be read-only or not
     *
     * @param Options $options
     *
     * @return int 0 - no restrictions, 1 - cannot add/delete, 2 = read only
     */
    protected function getState($options)
    {
        /** @var ConfigIdInterface $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (empty($className)) {
            return 0;
        }

        $fieldName = $this->typeHelper->getFieldName($configId);
        if (empty($fieldName)) {
            return 0;
        }

        $enumCode = $this->typeHelper->getEnumCode($className, $fieldName);
        if (!empty($enumCode)) {
            if ($options['config_is_new']) {
                // a new field reuses public enum
                return 2;
            }
            if ($this->typeHelper->isImmutable('enum', ExtendHelper::buildEnumValueClassName($enumCode))) {
                // an enum is immutable
                return 1;
            }
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_enum_value_collection';
    }
}
