<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\DoctrineIsolator;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AliceFixtureLoader;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Displays available initiated references.
 */
class AvailableReferencesController implements Controller
{
    /**
     * @var AliceFixtureLoader
     */
    protected $aliceLoader;

    /**
     * @var DoctrineIsolator
     */
    protected $doctrineIsolator;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(
        AliceFixtureLoader $aliceLoader,
        DoctrineIsolator $doctrineIsolator,
        KernelInterface $kernel
    ) {
        $this->aliceLoader = $aliceLoader;
        $this->doctrineIsolator = $doctrineIsolator;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--available-references',
                null,
                InputOption::VALUE_NONE,
                'Output available initiated references'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('available-references')) {
            return null;
        }

        $this->kernel->boot();
        $this->doctrineIsolator->initReferences();
        $this->kernel->shutdown();

        $table = new Table($output);
        $table->setHeaders(['ID', 'Object class']);

        foreach ($this->aliceLoader->getReferenceRepository()->toArray() as $key => $object) {
            $table->addRow(['@'.$key, get_class($object)]);
        }

        $table->render();

        return 0;
    }
}
