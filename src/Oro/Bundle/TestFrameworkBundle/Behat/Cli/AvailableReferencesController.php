<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\OroAliceLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\DoctrineIsolator;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AvailableReferencesController implements Controller
{
    /**
     * @var OroAliceLoader
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

    /**
     * @param OroAliceLoader $aliceLoader
     * @param DoctrineIsolator $doctrineIsolator
     * @param KernelInterface $kernel
     */
    public function __construct(
        OroAliceLoader $aliceLoader,
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

        foreach ($this->aliceLoader->getReferences() as $key => $object) {
            $table->addRow(['@'.$key, get_class($object)]);
        }

        $table->render();

        return 0;
    }
}
