<?php

namespace Oro\Bundle\LoggerBundle\EventSubscriber;

use Monolog\Logger;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleCommandSubscriber implements EventSubscriberInterface
{
    /** @var Logger */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => [['onConsoleCommand', -1]],
            ConsoleEvents::EXCEPTION => [['onConsoleException', -1]]
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ($event->commandShouldRun()) {
            $input = $event->getInput();

            $this->logger->info(
                sprintf('Launched command "%s"', $input->getFirstArgument()),
                $this->getContext($input)
            );
        }
    }

    /**
     * @param ConsoleExceptionEvent $event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $input = $event->getInput();

        $this->logger->error(
            sprintf(
                'An error occurred while running command "%s". %s',
                (string) $input,
                $event->getException()->getMessage()
            ),
            $this->getContext($input, ['exit_code' => $event->getExitCode()])
        );
    }

    /**
     * @param InputInterface $input
     * @param array $context
     *
     * @return array
     */
    private function getContext(InputInterface $input, array $context = [])
    {
        $class = new \ReflectionClass($input);

        $property = $class->getProperty('arguments');
        $property->setAccessible(true);

        $arguments = $property->getValue($input);
        if ($arguments) {
            $context['arguments'] = $arguments;
        }

        $property = $class->getProperty('options');
        $property->setAccessible(true);

        $options = $property->getValue($input);
        if ($options) {
            $context['options'] = $options;
        }

        return $context;
    }
}
