<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class ConsoleCommandListener
{
    /** @var FeatureChecker  */
    protected $featureChecker;

    /**
     * ConsoleCommandListener constructor.
     *
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        if (!$this->featureChecker->isResourceEnabled($command->getName(), 'commands')) {
            $event->disableCommand();
            $event->getOutput()->writeln(
                '<error>The feature that enables this command is turned off</error>'
            );
        } elseif ($command instanceof FeatureCheckerAwareInterface) {
            $command->setFeatureChecker($this->featureChecker);
        }
    }
}
