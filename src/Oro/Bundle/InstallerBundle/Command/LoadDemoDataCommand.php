<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class LoadDemoDataCommand extends ContainerAwareCommand
{
    const SEARCH_PATH = '/DataFixtures/Demo';

    /**
     * @var array
     */
    protected $allowedDirs = array();

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:demo:fixtures:load')
            ->setDescription('Load demo data fixtures to your database.')
            ->addOption(
                'directories',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Directories used to find fixture files'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setAllowedDirectories($input->getOption('directories'));

        $output->writeln('Loading demo data ...');
        $container = $this->getContainer();
        $loader    = new ContainerAwareLoader($container);
        /** @var BundleInterface $bundle */
        foreach ($container->get('kernel')->getBundles() as $bundle) {
            $path = $bundle->getPath() . self::SEARCH_PATH;
            if (is_dir($path) && $this->isAllowedDir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $executor = new ORMExecutor($em);
        $executor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        );
        $executor->execute($loader->getFixtures(), true);
    }

    /**
     * @param array $directories
     */
    protected function setAllowedDirectories(array $directories)
    {
        foreach ($directories as $dir) {
            $allowedDirectory = realpath($dir);
            if ($allowedDirectory) {
                $this->allowedDirs[] = $allowedDirectory;
            }
        }
    }

    /**
     * @param string $dir
     * @return bool
     */
    protected function isAllowedDir($dir)
    {
        $dir = realpath($dir);
        if ($this->allowedDirs) {
            foreach ($this->allowedDirs as $allowedDir) {
                if (strpos($dir, $allowedDir) === 0) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
}
