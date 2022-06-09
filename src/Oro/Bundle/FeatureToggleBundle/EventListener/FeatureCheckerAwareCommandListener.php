<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Injects the feature checker to commands
 * that implement {@see \Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface}.
 */
class FeatureCheckerAwareCommandListener
{
    private FeatureChecker $featureChecker;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command instanceof FeatureCheckerAwareInterface) {
            $command->setFeatureChecker($this->featureChecker);
        }
    }
}
