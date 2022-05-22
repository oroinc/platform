<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Disables a command when it is a part of some feature and this feature is disabled.
 * Injects the feature checker to commands that implement FeatureCheckerAwareInterface.
 */
class ConsoleCommandListener
{
    private FeatureChecker $featureChecker;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$this->featureChecker->isResourceEnabled($command->getName(), 'commands')) {
            $event->disableCommand();
            $event->getOutput()->writeln('<error>The feature that enables this command is turned off.</error>');
        } elseif ($command instanceof FeatureCheckerAwareInterface) {
            $command->setFeatureChecker($this->featureChecker);
        }
    }
}
