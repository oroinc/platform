<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalsCollectionType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ScheduleIntervalsCollectionTypeStub extends ScheduleIntervalsCollectionType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return ScheduleIntervalsCollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
