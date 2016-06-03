<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            'empty_value' => false,
            'choices' => $this->provider->getFormattingChoices()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
