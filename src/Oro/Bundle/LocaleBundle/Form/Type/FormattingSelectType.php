<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;

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
            'empty_value' => '',
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
        return OroChoiceType::NAME;
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
