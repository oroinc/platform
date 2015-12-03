<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class GenderType extends AbstractType
{
    const NAME = 'oro_gender';

    /**
     * @var GenderProvider
     */
    protected $genderProvider;

    /**
     * @param GenderProvider $genderProvider
     */
    public function __construct(GenderProvider $genderProvider)
    {
        $this->genderProvider = $genderProvider;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices'     => $this->genderProvider->getChoices(),
                'multiple'    => false,
                'expanded'    => false,
                'empty_value' => 'oro.user.form.choose_gender'
            )
        );
    }
}
