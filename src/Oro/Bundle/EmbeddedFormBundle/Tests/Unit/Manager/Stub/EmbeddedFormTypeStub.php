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
    public function getDefaultCss()
    {
    }

    public function getDefaultSuccessMessage()
    {
    }

    public function getParent()
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function getBlockPrefix()
    {
    }
}
