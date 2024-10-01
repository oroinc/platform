<?php

namespace Oro\Bundle\CronBundle\Twig;

use Oro\Bundle\CronBundle\Tools\ScheduleHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve information about schedule activity:
 *    - oro_cron_has_active_schedule
 */
class ScheduleExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_cron_has_active_schedule', [ScheduleHelper::class, 'hasActiveSchedule'])
        ];
    }
}
