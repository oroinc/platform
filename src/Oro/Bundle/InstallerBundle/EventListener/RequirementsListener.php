<?php

namespace Oro\Bundle\InstallerBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

use Oro\Bundle\InstallerBundle\Command\InstallCommandInterface;
use Oro\Bundle\InstallerBundle\Helper\RequirementsHelper;

class RequirementsListener
{
    /**
     * @var RequirementsHelper
     */
    protected $requirementsHelper;

    /**
     * @param RequirementsHelper $requirementsHelper
     */
    public function __construct(RequirementsHelper $requirementsHelper)
    {
        $this->requirementsHelper = $requirementsHelper;
    }

    /**
     * @param ConsoleCommandEvent $event
     * @throws \RuntimeException
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ($event->getCommand() instanceof InstallCommandInterface) {
            return;
        }

        $requirements = $this->requirementsHelper->getNotFulfilledRequirements();

        if ($requirements) {
            foreach ($requirements as $requirement) {
                $event->getOutput()->writeln(
                    sprintf('<error>%s</error>', $requirement->getTestMessage())
                );
            }

            throw new \RuntimeException('Not all requirements were fulfilled');
        }
    }
}
