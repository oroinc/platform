<?php

namespace Oro\Bundle\PlatformBundle\Command;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Get list of Doctrine listeners which can be disabled during process console command
 */
class OptionalListenersCommand extends Command
{
    protected static $defaultName = 'oro:platform:optional-listeners';

    /** @var OptionalListenerManager */
    private $optionalListenerManager;

    /**
     * @param OptionalListenerManager $optionalListenerManager
     */
    public function __construct(OptionalListenerManager $optionalListenerManager)
    {
        $this->optionalListenerManager = $optionalListenerManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Get list of Doctrine listeners which can be disabled during process console command');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('List of optional listeners:');
        foreach ($this->optionalListenerManager->getListeners() as $listener) {
            $output->writeln(sprintf('  <comment>> %s</comment>', $listener));
        }
    }
}
