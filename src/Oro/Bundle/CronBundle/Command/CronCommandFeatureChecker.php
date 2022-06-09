<?php

namespace Oro\Bundle\CronBundle\Command;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Disallows to execute a CRON command when there are at least one disabled feature that includes this command.
 */
class CronCommandFeatureChecker implements CronCommandFeatureCheckerInterface
{
    private FeatureChecker $featureChecker;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function isFeatureEnabled(string $commandName): bool
    {
        return $this->featureChecker->isResourceEnabled($commandName, 'cron_jobs');
    }
}
