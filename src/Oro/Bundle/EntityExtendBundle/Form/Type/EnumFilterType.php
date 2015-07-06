<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractChoiceType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;

class EnumFilterType extends AbstractChoiceType
{
    const NAME = 'oro_enum_filter';

    /**
     * @var EnumValueProvider
     */
    protected $valueProvider;

    /**
     * @param TranslatorInterface $translator
     * @param EnumValueProvider $valueProvider
     */
    public function __construct(TranslatorInterface $translator, EnumValueProvider $valueProvider)
    {
        parent::__construct($translator);
        $this->valueProvider = $valueProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaultFieldOptions = [
            'multiple' => true
        ];

        $resolver->setDefaults(
            [
                // either enum_code or class must be specified
                'enum_code'     => null,
                'class'         => null,
                'field_options' => $defaultFieldOptions
            ]
        );
        $resolver->setNormalizers(
            [
                'class' => function (Options $options, $value) {
                    if ($value !== null) {
                        return $value;
                    }

                    if (empty($options['enum_code'])) {
                        throw new InvalidOptionsException('Either "class" or "enum_code" must option must be set.');
                    }

                    $class = ExtendHelper::buildEnumValueClassName($options['enum_code']);
                    if (!is_a($class, 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue', true)) {
                        throw new InvalidOptionsException(
                            sprintf(
                                '"%s" must be a child of "%s"',
                                $class,
                                'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue'
                            )
                        );
                    }

                    return $class;
                },
                // this normalizer allows to add/override field_options options outside
                'field_options' => function (Options $options, $value) use (&$defaultFieldOptions) {
                    if (isset($options['class'])) {
                        $nullValue = null;
                        if ($options->has('null_value')) {
                            $nullValue = $options->get('null_value');
                        }
                        $value['choices'] = $this->getChoices($options['class'], $nullValue);
                    } else {
                        $value['choices'] = [];
                    }

                    return array_merge($defaultFieldOptions, $value);
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceFilterType::NAME;
    }

    /**
     * @param string      $enumValueClassName
     * @param string|null $nullValue
     *
     * @return array
     */
    protected function getChoices($enumValueClassName, $nullValue)
    {
        $choices = [];
        if (!empty($nullValue)) {
            $choices[$nullValue] = $this->translator->trans('oro.entity_extend.datagrid.enum.filter.empty');
        }

        if (!empty($enumValueClassName)) {
            $choices = array_merge($choices, $this->valueProvider->getEnumChoices($enumValueClassName));
        }

        return $choices;
    }
}
