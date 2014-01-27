<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

class LoadDataFixturesCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:installer:fixtures:load')
            ->setDescription('Load versioned data fixtures. By default will load main data fixtures')
            ->addOption('load-demo', null, InputOption::VALUE_OPTIONAL, 'True if need to load demo data', 'false')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show list of fixtures without apply');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container   = $this->getContainer();
        $loader = $container->get('oro_installer.fixtures.loader');
        $loader->isLoadDemoData($input->getOption('load-demo') == 'false' ? false : true);
        $fixtures = $loader->getFixtures();
        if ($input->getOption('dry-run')) {
            $this->outputFixtures($input, $output, $fixtures);
        } else {
            $this->processFixtures($input, $output, $fixtures);
        }
    }

    /**
     * Output list of fixtures
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $fixtures
     */
    protected function outputFixtures(InputInterface $input, OutputInterface $output, $fixtures)
    {
        $output->writeln(
            sprintf(
                'List of %s fixtures data ...',
                $input->getOption('load-demo') == 'false' ? 'main' : 'demo'
            )
        );
        foreach ($fixtures as $fixture) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', get_class($fixture)));
        }
    }

    /**
     * Process fixtures
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $fixtures
     */
    protected function processFixtures(InputInterface $input, OutputInterface $output, $fixtures)
    {
        $output->writeln(
            sprintf(
                'Loading %s fixtures data ...',
                $input->getOption('load-demo') == 'false' ? 'main' : 'demo'
            )
        );

        $executor = new ORMExecutor($this->getContainer()->get('doctrine.orm.entity_manager'));
        $executor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        );
        $executor->execute($fixtures, true);
    }
}
