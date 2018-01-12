<?php

namespace Oro\Bundle\PlatformBundle\Provider\Console;

use Oro\Bundle\PlatformBundle\Command\OptionalListenersCommand;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class OptionalListenersGlobalOptionsProvider extends AbstractGlobalOptionsProvider
{
    const ALL_OPTIONAL_LISTENERS_VALUE = 'all';
    const DISABLE_OPTIONAL_LISTENERS   = 'disabled-listeners';

    /**
     * @var OptionalListenerManager
     */
    protected $listenersManager;

    /**
     * @param OptionalListenerManager $listenerManager
     */
    public function __construct(OptionalListenerManager $listenerManager)
    {
        $this->listenersManager = $listenerManager;
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobalOptions(Command $command)
    {
        $options = [
            new InputOption(
                self::DISABLE_OPTIONAL_LISTENERS,
                null,
                InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
                sprintf(
                    'Disable optional listeners, "%s" to disable all listeners, '
                    .'command "%s" shows all listeners',
                    self::ALL_OPTIONAL_LISTENERS_VALUE,
                    OptionalListenersCommand::NAME
                )
            ),
        ];

        $this->addOptionsToCommand($command, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveGlobalOptions(InputInterface $input)
    {
        $listeners = $this->getListenersToDisable($input);
        if (!empty($listeners)) {
            $this->listenersManager->disableListeners($listeners);
        }
    }

    /**
     *
     * @param InputInterface $input
     * @return array
     */
    protected function getListenersToDisable(InputInterface $input)
    {
        $listeners = [];

        $listenerList = $input->getOption(self::DISABLE_OPTIONAL_LISTENERS);
        if (!empty($listenerList)) {
            if (count($listenerList) === 1 && $listenerList[0] === self::ALL_OPTIONAL_LISTENERS_VALUE) {
                $listeners = $this->listenersManager->getListeners();
            } else {
                $listeners = $listenerList;
            }
        }

        return $listeners;
    }
}
