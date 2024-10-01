<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalsCollectionType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ScheduleIntervalsCollectionTypeStub extends ScheduleIntervalsCollectionType
{
    #[\Override]
    public function getName()
    {
        return ScheduleIntervalsCollectionType::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }
}
