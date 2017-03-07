<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Functional\Stubs;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormType;

class EmbeddedFormStub extends EmbeddedFormType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('submit', SubmitType::class);
    }
}
