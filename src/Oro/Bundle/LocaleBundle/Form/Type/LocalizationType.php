<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LocalizationType extends AbstractType
{
    const NAME = 'oro_localization';

    /** @var string */
    protected $dataClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', ['required' => true, 'label' => 'oro.locale.localization.name.label'])
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'required' => true,
                    'label' => 'oro.locale.localization.titles.label',
                    'options' => [
                        'constraints' => [new NotBlank(['message' => 'oro.locale.localization.titles.blank'])]
                    ]
                ]
            )
            ->add(
                'languageCode',
                'oro_language_select',
                ['required' => true, 'label' => 'oro.locale.localization.language_code.label']
            )
            ->add(
                'formattingCode',
                'oro_formatting_select',
                ['required' => true, 'label' => 'oro.locale.localization.formatting_code.label']
            )
            ->add(
                'parent',
                LocalizationParentSelectType::NAME,
                ['required' => false, 'label' => 'oro.locale.localization.parent_localization.label']
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }
}
