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
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'handle_primary' => false,
                'type'           => 'oro_entity_extend_enum_value'
            ]
        );

        $typeHelper = $this->typeHelper;

        $resolver->setNormalizers(
            [
                'disabled'          => function (Options $options, $value) {
                    return $this->isDisabled($options) ? true : $value;
                },
                'options'           => function (Options $options, $value) use ($typeHelper) {
                    return [
                        'allow_multiple_selection' => ($typeHelper->getFieldType($options['config_id']) === 'multiEnum')
                    ];
                },
                'allow_add'         => function (Options $options, $value) {
                    return $options['disabled'] || $this->isDisabled($options, 'add') ? false : $value;
                },
                'allow_delete'      => function (Options $options, $value) {
                    return $options['disabled'] || $this->isDisabled($options, 'delete') ? false : $value;
                },
                'validation_groups' => function (Options $options, $value) {
                    return $options['disabled'] ? false : $value;
                },
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
