<?php

namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;


use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AvailableEmbeddedFormType extends AbstractType
{
    /**
     * @var \Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager
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
     * {@inhertidoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
                'choices' => $this->manager->getAll()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_available_embedded_forms';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}