<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\SuiteRepository;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides the ability to filter the features by topics.
 */
class FeaturesTopicController implements Controller
{
    public function __construct(
        private SuiteRepository $suiteRepository,
        private SpecificationFinder $specificationFinder,
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === $input->getParameterOption('--topics')) {
            return null;
        }

        $topics = \explode(',', $input->getOption('topics'));
        $config = $this->featureTopics;
        if ($input->getOption('topics') === null) {
            // print all the available topics
            foreach ($config as $topic => $regexs) {
                $output->writeln($topic);
            }
            return 0;
        }

        if ($config === null) {
            $output->writeln('<error>The "feature_topics" config is not provided in behat.yml</error>');
            return 1;
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
        $regexs = implode('|', array_map(function ($topic) use ($config) {
            return \implode('|', $config[$topic]);
        }, $selectedTopics));

        foreach ($this->suiteRepository->getSuites() as $suite) {
            $iterators = $this->specificationFinder->findSuitesSpecifications([$suite]);
            foreach ($iterators as $iterator) {
                /** @var FeatureNode $item */
                foreach ($iterator as $item) {
                    $fileContent = \file_get_contents($item->getFile());
                    // if $fileContent matches the regular expressions, output it's path
                    if (\preg_match('/' . $regexs . '/', $fileContent) === 1) {
                        $output->writeln($item->getFile());
                    }
                }
            }
        }

        return 0;
    }
}
