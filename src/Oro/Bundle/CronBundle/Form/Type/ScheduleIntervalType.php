<?php

namespace Oro\Bundle\CronBundle\Form\Type;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScheduleIntervalType extends AbstractType
{
    const NAME = 'oro_cron_schedule_interval';
    const ACTIVE_AT_FIELD = 'activeAt';
    const DEACTIVATE_AT_FIELD = 'deactivateAt';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::ACTIVE_AT_FIELD, OroDateTimeType::class, [
                'required' => false
            ])
            ->add(self::DEACTIVATE_AT_FIELD, OroDateTimeType::class, [
                'required' => false
            ]);
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
        return self::NAME;
    }
}
