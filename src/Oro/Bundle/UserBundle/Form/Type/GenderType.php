<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting user gender.
 *
 * This form type provides a choice field for selecting gender values.
 * It uses the {@see GenderProvider} to retrieve available gender options and displays
 * them as a dropdown with a placeholder for no selection.
 */
class GenderType extends AbstractType
{
    const NAME = 'oro_gender';

    /**
     * @var GenderProvider
     */
    protected $genderProvider;

    public function __construct(GenderProvider $genderProvider)
    {
        $this->genderProvider = $genderProvider;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices'     => $this->genderProvider->getChoices(),
                'multiple'    => false,
                'expanded'    => false,
                'placeholder' => 'oro.user.form.choose_gender',
                'translatable_options' => false
            )
        );
    }
}
