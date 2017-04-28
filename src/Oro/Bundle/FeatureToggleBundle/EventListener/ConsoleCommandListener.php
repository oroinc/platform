<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Exception\FeatureDisabledException;

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
     * @throws FeatureDisabledException
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        if (!$this->featureChecker->isResourceEnabled($command->getName(), 'commands')) {
            $event->disableCommand();
            $event->getOutput()->writeln(
                '<error>Feature for this command is disabled. Please enable it to run the command.</error>'
            );
        } elseif ($command instanceof FeatureCheckerAwareInterface) {
            $command->setFeatureChecker($this->featureChecker);
        }
    }
}
