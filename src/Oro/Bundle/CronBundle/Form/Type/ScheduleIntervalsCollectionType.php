<?php

namespace Oro\Bundle\CronBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for schedule intervals collection.
 */
class ScheduleIntervalsCollectionType extends AbstractType
{
    const NAME = 'oro_cron_schedule_intervals_collection';

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => ScheduleIntervalType::class,
            'required' => false,
            'by_reference' => false
        ]);
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
