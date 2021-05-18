<?php
declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Provider\Console;

use Oro\Bundle\PlatformBundle\Command\OptionalListenersCommand;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Adds --disabled-listeners global command option to disable optional listeners.
 */
class OptionalListenersGlobalOptionsProvider extends AbstractGlobalOptionsProvider
{
    public const DISABLE_OPTIONAL_LISTENERS = 'disabled-listeners';

    protected OptionalListenerManager $listenersManager;

    public function __construct(OptionalListenerManager $listenerManager)
    {
        $this->listenersManager = $listenerManager;
    }

    public function addGlobalOptions(Command $command)
    {
        $options = [
            new InputOption(
                self::DISABLE_OPTIONAL_LISTENERS,
                null,
                InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
                \sprintf(
                    '"<comment>all</comment>" or run <info>%s</info> to see available',
                    OptionalListenersCommand::getDefaultName(),
                )
            ),
        ];

        $this->addOptionsToCommand($command, $options);
    }

    public function resolveGlobalOptions(InputInterface $input): void
    {
        $listeners = $this->getListenersToDisable($input);
        if (!empty($listeners)) {
            $this->listenersManager->disableListeners($listeners);
        }
    }

    protected function getListenersToDisable(InputInterface $input): array
    {
        $listeners = [];

        $listenerList = $input->getOption(self::DISABLE_OPTIONAL_LISTENERS);
        if (!empty($listenerList)) {
            if (count($listenerList) === 1 && $listenerList[0] === 'all') {
                $listeners = $this->listenersManager->getListeners();
            } else {
                $listeners = $listenerList;
            }
        }

        return $listeners;
    }
}
