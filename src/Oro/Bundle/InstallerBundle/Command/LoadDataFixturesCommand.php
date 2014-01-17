<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

use Oro\Bundle\InstallerBundle\CommandExecutor;

class LoadDataFixturesCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:data:fixtures:load')
            ->setDescription('Load versioned data fixtures. By default will load main data fixtures')
            ->addOption('load-demo', null, InputOption::VALUE_OPTIONAL, 'True if need to load demo data', false);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container   = $this->getContainer();
        $output->writeln('Loading data ...');
        $executor = new ORMExecutor($container->get('doctrine.orm.entity_manager'));
        $loader = $container->get('oro_installer.fixtures.loader');
        $loader->isLoadDemoData($input->getOption('load-demo'));
        $executor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        );
        $executor->execute($loader->getFixtures(), true);
    }
}
