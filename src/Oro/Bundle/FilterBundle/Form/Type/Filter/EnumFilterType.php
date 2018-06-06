<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class EnumFilterType extends AbstractMultiChoiceType
{
    const NAME = 'oro_enum_filter';
    const TYPE_IN = '1';
    const TYPE_NOT_IN = '2';
    const EQUAL = '3';
    const NOT_EQUAL = '4';

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
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaultFieldOptions = [
            'multiple' => true,
        ];

        $resolver->setDefaults(
            [
                // either enum_code or class must be specified
                'enum_code'     => null,
                'class'         => null,
                'field_options' => $defaultFieldOptions,
                'operator_choices' => [
                    $this->translator->trans('oro.filter.form.label_type_in') => self::TYPE_IN,
                    $this->translator->trans('oro.filter.form.label_type_not_in') => self::TYPE_NOT_IN,
                ],
            ]
        );

        $resolver->setNormalizer(
            'class',
            function (Options $options, $value) {
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
            }
        );

        // this normalizer allows to add/override field_options options outside
        $resolver->setNormalizer(
            'field_options',
            function (Options $options, $value) use (&$defaultFieldOptions) {
                if (isset($options['class'])) {
                    $nullValue = null;
                    if ($options->offsetExists('null_value')) {
                        $nullValue = $options->offsetGet('null_value');
                    }
                    $value['choices'] = $this->getChoices($options['class'], $nullValue);
                } else {
                    $value['choices'] = [];
                }

                return array_merge($defaultFieldOptions, $value);
            }
        );
    }

    /**
     * Convert value to string.
     *
     * AbstractEnumValue declare primary key as string.
     * For enums with numerical PK value should be converted to string for correct types in DB query.
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($value) {
                    return $value;
                },
                function ($value) {
                    if (is_array($value) && array_key_exists('value', $value)) {
                        foreach ($value['value'] as &$data) {
                            $data = (string)$data;
                        }
                    }

                    return $value;
                }
            )
        );
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceFilterType::class;
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
            $choices[$this->translator->trans('oro.entity_extend.datagrid.enum.filter.empty')] = $nullValue;
        }

        if (!empty($enumValueClassName)) {
            $choices = $this->valueProvider->getEnumChoices($enumValueClassName) + $choices;
        }

        return $choices;
    }
}
