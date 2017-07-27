<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\SuiteRepository;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AvailableSuitesController implements Controller
{
    /**
     * @var SuiteRepository
     */
    private $suiteRepository;

    /**
     * @var SpecificationFinder
     */
    private $specificationFinder;

    /**
     * @param SuiteRepository $suiteRepository
     * @param SpecificationFinder $specificationFinder
     */
    public function __construct(SuiteRepository $suiteRepository, SpecificationFinder $specificationFinder)
    {
        $this->suiteRepository = $suiteRepository;
        $this->specificationFinder = $specificationFinder;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--available-suites',
                null,
                InputOption::VALUE_NONE,
                'Show all available test suites.'.PHP_EOL.
                'Suites can be configured automatically by extensions, and manually by configuration'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('available-suites')) {
            return;
        }

        $suites = [];

        foreach ($this->suiteRepository->getSuites() as $suite) {
            $iterators = $this->specificationFinder->findSuitesSpecifications([$suite]);
            $countFeatures = array_sum(array_map('iterator_count', $iterators));

            if (0 !== $countFeatures) {
                $suites[$suite->getName()] = $countFeatures;
            }
        }

        arsort($suites);

        foreach (array_keys($suites) as $suite) {
            $output->writeln($suite);
        }

        return 0;
    }
}
