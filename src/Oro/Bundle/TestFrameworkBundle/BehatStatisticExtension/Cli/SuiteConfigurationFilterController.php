<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This should be proceed after the Behat\Behat\Gherkin\Cli\FilterController
 */
class SuiteConfigurationFilterController implements Controller
{
    public function __construct(
        private SuiteConfigurationRegistry $suiteConfigRegistry,
        private array $suiteConfig,
        private array $sets,
        private array $featureTopics
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--topics',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output all the available topics if not specified, ' .
                'otherwise output the files that match the selected topics',
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (false !== $input->getParameterOption('--topics')) {
            $topics = \explode(',', $input->getOption('topics'));
            $config = $this->featureTopics;
            if ($input->getOption('topics') === null) {
                // print all the available topics
                foreach ($config as $topic => $regexs) {
                    $output->writeln($topic);
                }
                return 0;
            }
            $allTopics = array_keys($config);
            $invalidTopics = \array_diff($topics, $allTopics);
            if (\count($invalidTopics) > 0) {
                $output->writeln(
                    '<info>Invalid topics:</info> <error>' . \implode(',', $invalidTopics) . "</error>\n" .
                    '<info>Available topics are:</info> ' . \implode(',', $allTopics) . '.'
                );
                return 1;
            }

            $selectedTopics = \array_intersect($topics, $allTopics);
            $regex = implode('|', array_map(function ($topic) use ($config) {
                return \implode('|', $config[$topic]);
            }, $selectedTopics));

            $this->suiteConfigRegistry->setRegex($regex);
        }

        $this->suiteConfigRegistry->setSuiteConfigurations($this->suiteConfig);
        $this->suiteConfigRegistry->filterConfiguration();
        $this->suiteConfigRegistry->setSets($this->sets);
    }
}
