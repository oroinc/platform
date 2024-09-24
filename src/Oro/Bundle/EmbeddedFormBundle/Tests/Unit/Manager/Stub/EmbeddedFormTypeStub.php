<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager\Stub;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmbeddedFormTypeStub implements EmbeddedFormInterface, FormTypeInterface
{
    #[\Override]
    public function getDefaultCss()
    {
    }

    #[\Override]
    public function getDefaultSuccessMessage()
    {
    }

    #[\Override]
    public function getParent()
    {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
    }

    #[\Override]
    public function getBlockPrefix()
    {
    }
}
