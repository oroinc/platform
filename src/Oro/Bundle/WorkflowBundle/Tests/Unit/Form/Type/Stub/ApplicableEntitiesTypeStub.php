<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type\Stub;

use Doctrine\Common\Util\Inflector;
use Oro\Bundle\WorkflowBundle\Form\Type\ApplicableEntitiesType;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicableEntitiesTypeStub extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    StubEntity::class => Inflector::tableize(StubEntity::class),
                    \stdClass::class => Inflector::tableize(\stdClass::class)
                ]
            ]
        );
    }

    /**
     * {@inheritDoc}
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
        return ApplicableEntitiesType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
