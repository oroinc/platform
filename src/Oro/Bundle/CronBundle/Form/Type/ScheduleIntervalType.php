<?php

namespace Oro\Bundle\CronBundle\Form\Type;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for editing schedule interval data with activation and deactivation dates.
 *
 * This form type provides two datetime fields for defining when a schedule becomes active
 * and when it should be deactivated. It is designed to work with entities implementing
 * {@see ScheduleIntervalInterface} and is commonly used in:
 * - Price list scheduling (defining when price lists are active)
 * - Promotion scheduling (setting promotion availability windows)
 * - Any feature requiring time-based activation/deactivation
 *
 * The form validates that the data_class option implements {@see ScheduleIntervalInterface},
 * ensuring type safety and preventing misconfiguration. Both datetime fields are optional,
 * allowing for open-ended schedules (e.g., active from a date with no end, or active until
 * a date with no specific start).
 *
 * This type is typically used within {@see ScheduleIntervalsCollectionType} to manage multiple
 * schedule intervals for a single entity.
 */
class ScheduleIntervalType extends AbstractType
{
    const NAME = 'oro_cron_schedule_interval';
    const ACTIVE_AT_FIELD = 'activeAt';
    const DEACTIVATE_AT_FIELD = 'deactivateAt';

    #[\Override]
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

    #[\Override]
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

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
