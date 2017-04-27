<?php

namespace Oro\Bundle\CacheBundle\Command;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionHandlerInterface;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionScheduledHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InvalidateCacheScheduleCommand extends ContainerAwareCommand
{
    const NAME = 'oro:cache:invalidate:schedule';

    /**
     * @internal
     */
    const ARGUMENT_SERVICE_NAME = InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME;

    const ARGUMENT_PARAMETERS = 'parameters';

    /**
     * {@inheritDoc}
     */
    public function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Invalidate Cache')
            ->addArgument(
                self::ARGUMENT_SERVICE_NAME,
                InputArgument::REQUIRED,
                'Service with functionality to invalidate cache'
            )
            ->addArgument(
                self::ARGUMENT_PARAMETERS,
                InputArgument::OPTIONAL,
                'Serialized parameters for service'
            );
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $input->getArgument(self::ARGUMENT_SERVICE_NAME);
        $dataStorage = $this->buildDataStorage($input);

        $this->getService($service)->handle($dataStorage);
    }

    /**
     * @param InputInterface $input
     *
     * @return InvalidateCacheDataStorage
     *
     * @throws InvalidArgumentException
     */
    private function buildDataStorage(InputInterface $input)
    {
        $arguments = $input->getArgument(self::ARGUMENT_PARAMETERS);

        return new InvalidateCacheDataStorage(unserialize($arguments));
    }

    /**
     * @param string $service
     *
     * @return InvalidateCacheActionHandlerInterface|object
     */
    private function getService($service)
    {
        return $this->getContainer()->get($service);
    }
}
