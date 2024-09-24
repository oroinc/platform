<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type\Stub;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\WorkflowBundle\Form\Type\ApplicableEntitiesType;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicableEntitiesTypeStub extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $inflector = (new InflectorFactory())->build();

        $resolver->setDefaults(
            [
                'choices' => [
                    $inflector->tableize(StubEntity::class) => StubEntity::class,
                    $inflector->tableize(\stdClass::class) => \stdClass::class
                ]
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return ApplicableEntitiesType::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
