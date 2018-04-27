<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'handle_primary' => false,
                'entry_type'     => EnumValueType::class
            ]
        );

        $resolver->setNormalizer(
            'disabled',
            function (Options $options, $value) {
                return $this->isDisabled($options) ? true : $value;
            }
        )
        ->setNormalizer(
            'entry_options',
            function (Options $options, $value) {
                return array_replace(
                    ['allow_multiple_selection' => ($this->isMultipleSelectEnable($options['config_id']))],
                    (array) $value
                );
            }
        )
        ->setNormalizer(
            'allow_add',
            function (Options $options, $value) {
                return $options['disabled'] || $this->isDisabled($options, 'add') ? false : $value;
            }
        )
        ->setNormalizer(
            'allow_delete',
            function (Options $options, $value) {
                return $options['disabled'] || $this->isDisabled($options, 'delete') ? false : $value;
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
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['multiple'] = $this->isMultipleSelectEnable($options['config_id']);
        $view->vars['show_form_when_empty'] = false;
    }

    /**
     * @param $field
     * @return bool
     */
    protected function isMultipleSelectEnable($field)
    {
        return $this->typeHelper->getFieldType($field) === 'multiEnum';
    }

    /**
     * Checks if the given constraint is applied or not
     *
     * @param Options     $options
     * @param string|null $constraintName Can be: null, 'add', 'delete'
     *
     * @return bool
     */
    protected function isDisabled($options, $constraintName = null)
    {
        /** @var ConfigIdInterface $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (empty($className)) {
            return false;
        }

        $fieldName = $this->typeHelper->getFieldName($configId);
        if (empty($fieldName)) {
            return false;
        }

        $enumCode = $this->typeHelper->getEnumCode($className, $fieldName);
        if (!empty($enumCode)) {
            if ($options['config_is_new']) {
                // a new field reuses public enum
                return true;
            }
            if ($constraintName) {
                $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
                if ($this->typeHelper->isImmutable('enum', $enumValueClassName, null, $constraintName)) {
                    // is immutable
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_entity_extend_enum_value_collection';
    }
}
