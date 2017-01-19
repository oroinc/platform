<?php

namespace Oro\Bundle\MigrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

use Oro\Bundle\EmailBundle\Async\Topics as EmailTopics;
use Oro\Bundle\MigrationBundle\Migration\Loader\DataFixturesLoader;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class LoadDataFixturesCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:migration:data:load';

    const MAIN_FIXTURES_TYPE = 'main';
    const DEMO_FIXTURES_TYPE = 'demo';

    const MAIN_FIXTURES_PATH = 'Migrations/Data/ORM';
    const DEMO_FIXTURES_PATH = 'Migrations/Data/Demo/ORM';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription('Load data fixtures.')
            ->addOption(
                'fixtures-type',
                null,
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Select fixtures type to be loaded (%s or %s). By default - %s',
                    self::MAIN_FIXTURES_TYPE,
                    self::DEMO_FIXTURES_TYPE,
                    self::MAIN_FIXTURES_TYPE
                ),
                self::MAIN_FIXTURES_TYPE
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs list of fixtures without apply them')
            ->addOption(
                'bundles',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'A list of bundle names to load data from'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'A list of bundle names which fixtures should be skipped'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->checkDisableListeners($input)) {
            $this->disableDefaultListeners();
        }

        $fixtures = null;
        try {
            $fixtures = $this->getFixtures($input, $output);
        } catch (\RuntimeException $ex) {
            $output->writeln('');
            $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));

            return $ex->getCode() == 0 ? 1 : $ex->getCode();
        }

        if (!empty($fixtures)) {
            if ($input->getOption('dry-run')) {
                $this->outputFixtures($input, $output, $fixtures);
            } else {
                $this->processFixtures($input, $output, $fixtures);
            }
        }

        if ($this->checkDisableListeners($input)) {
            $this->scheduleSearchReindexAndUpdateEmailAssociation();
            $this->enableDefaultListeners();
        }

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return array
     * @throws \RuntimeException if loading of data fixtures should be terminated
     */
    protected function getFixtures(InputInterface $input, OutputInterface $output)
    {
        /** @var DataFixturesLoader $loader */
        $loader              = $this->getContainer()->get('oro_migration.data_fixtures.loader');
        $bundles             = $input->getOption('bundles');
        $excludeBundles      = $input->getOption('exclude');
        $fixtureRelativePath = $this->getFixtureRelativePath($input);

        /** @var BundleInterface $bundle */
        foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
            if (!empty($bundles) && !in_array($bundle->getName(), $bundles)) {
                continue;
            }
            if (!empty($excludeBundles) && in_array($bundle->getName(), $excludeBundles)) {
                continue;
            }
            $path = $bundle->getPath() . $fixtureRelativePath;
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        return $loader->getFixtures();
    }

    /**
     * Output list of fixtures
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $fixtures
     */
    protected function outputFixtures(InputInterface $input, OutputInterface $output, $fixtures)
    {
        $output->writeln(
            sprintf(
                'List of "%s" data fixtures ...',
                $this->getTypeOfFixtures($input)
            )
        );
        foreach ($fixtures as $fixture) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', get_class($fixture)));
        }
    }

    /**
     * Process fixtures
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $fixtures
     */
    protected function processFixtures(InputInterface $input, OutputInterface $output, $fixtures)
    {
        $output->writeln(
            sprintf(
                'Loading "%s" data fixtures ...',
                $this->getTypeOfFixtures($input)
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

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function getTypeOfFixtures(InputInterface $input)
    {
        return $input->getOption('fixtures-type');
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function getFixtureRelativePath(InputInterface $input)
    {
        $fixtureRelativePath = $this->getTypeOfFixtures($input) == self::DEMO_FIXTURES_TYPE
            ? self::DEMO_FIXTURES_PATH
            : self::MAIN_FIXTURES_PATH;

        return str_replace('/', DIRECTORY_SEPARATOR, '/' . $fixtureRelativePath);
    }

    /**
     * If we don't receive a disabled-listeners option we disable three listeners by default:
     * - SearchIndex listener (to prevent a lot of reindex messages)
     * - EntityListener in EmailBundle (to prevent a lot of UpdateEmailAssociations messages)
     * - WorkflowEventTrigger (to prevent additional reindexing)
     * After loading the demo data we send the messages manually and re-enable the listeners
     */
    protected function disableDefaultListeners()
    {
        $this->getOptionalListenerManager()->disableListeners($this->getListenersToDisable());
    }

    protected function enableDefaultListeners()
    {
        $this->getOptionalListenerManager()->enableListeners($this->getListenersToDisable());
    }

    protected function scheduleSearchReindexAndUpdateEmailAssociation()
    {
        $this->getSearchIndexer()->reindex();

        $this
            ->getProducer()
            ->send(EmailTopics::UPDATE_ASSOCIATIONS_TO_EMAILS, []);
    }

    /**
     * @param InputInterface $input
     *
     * @return bool
     */
    protected function checkDisableListeners(InputInterface $input)
    {
        return (
            ! $input->getOption('disabled-listeners') &&
            $input->getOption('fixtures-type') !== self::MAIN_FIXTURES_TYPE
        );
    }

    /**
     * @return array
     */
    protected function getListenersToDisable()
    {
        return [
            'oro_search.index_listener',
            'oro_email.listener.entity_listener',
            'oro_workflow.listener.event_trigger_collector'
        ];
    }

    /**
     * @return OptionalListenerManager
     */
    protected function getOptionalListenerManager()
    {
        return $this->getContainer()->get('oro_platform.optional_listeners.manager');
    }

    /**
     * @return IndexerInterface
     */
    protected function getSearchIndexer()
    {
        return $this->getContainer()->get('oro_search.async.indexer');
    }

    /**
     * @return MessageProducerInterface
     */
    protected function getProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }
}
