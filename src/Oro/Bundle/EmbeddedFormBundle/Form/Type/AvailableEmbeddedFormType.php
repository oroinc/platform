<?php

namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvailableEmbeddedFormType extends AbstractType
{
    /**
     * @var EmbeddedFormManager
     */
    protected $manager;

    /**
     * @param EmbeddedFormManager $manager
     */
    public function __construct(EmbeddedFormManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                // TODO: remove 'choices_as_values' option below in scope of BAP-15236
                'choices_as_values' => true,
                'choices' => array_flip($this->manager->getAll()),
            ]
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
        return 'oro_available_embedded_forms';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
