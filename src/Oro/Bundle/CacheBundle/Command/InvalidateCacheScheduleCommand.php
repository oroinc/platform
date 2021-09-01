<?php
declare(strict_types=1);

namespace Oro\Bundle\CacheBundle\Command;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionHandlerInterface;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionScheduledHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Schedules cache invalidation using a specified service and parameters.
 */
class InvalidateCacheScheduleCommand extends Command
{
    /**
     * @internal
     */
    public const ARGUMENT_SERVICE_NAME = InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME;

    public const ARGUMENT_PARAMETERS = 'parameters';

    /** @var string */
    protected static $defaultName = 'oro:cache:invalidate:schedule';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addArgument(
                self::ARGUMENT_SERVICE_NAME,
                InputArgument::REQUIRED,
                'Service with functionality to invalidate cache'
            )
            ->addArgument(
                self::ARGUMENT_PARAMETERS,
                InputArgument::OPTIONAL,
                'Serialized parameters for service'
            )
            ->setDescription('Schedules cache invalidation using a specified service and parameters.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command schedules cache invalidation using a specified service and parameters.

  <info>php %command.full_name% <service></info>
  <info>php %command.full_name% <service> <serialized-parameters></info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $input->getArgument(self::ARGUMENT_SERVICE_NAME);
        $dataStorage = $this->buildDataStorage($input);

        $this->getService($service)->handle($dataStorage);

        return 0;
    }

    private function buildDataStorage(InputInterface $input): InvalidateCacheDataStorage
    {
        $arguments = $input->getArgument(self::ARGUMENT_PARAMETERS);

        return new InvalidateCacheDataStorage(\unserialize($arguments));
    }

    private function getService(string $service): InvalidateCacheActionHandlerInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getContainer()->get($service);
    }

    private function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
