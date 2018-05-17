<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormattingSelectType extends AbstractType
{
    const NAME = 'oro_formatting_select';

    /**
     * @var LocalizationChoicesProvider
     */
    private $provider;

    /**
     * @param LocalizationChoicesProvider $provider
     */
    public function __construct(LocalizationChoicesProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'placeholder' => '',
            'choices' => $this->provider->getFormattingChoices(),
            'translatable_options' => false,
            'configs' => [
                'placeholder' => 'oro.locale.localization.form.placeholder.select_formatting'
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroChoiceType::class;
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
        return static::NAME;
    }
}
