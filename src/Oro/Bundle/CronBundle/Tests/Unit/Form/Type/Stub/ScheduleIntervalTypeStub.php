<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScheduleIntervalTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return ScheduleIntervalType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer('data_class', function (Options $options, $value) {
            if (!is_a($value, ScheduleIntervalInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Class %s given in data_class option must implement %s',
                    $value,
                    ScheduleIntervalInterface::class
                ));
            }

            return $value;
        });
    }
}
