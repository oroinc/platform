<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\Exception\SuiteNotFoundException;
use Behat\Testwork\Suite\SuiteRepository;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AvailableSuitesGroupController implements Controller
{
    const UNGROUPED_GROUP = 'Ungrouped';

    /**
     * @var SuiteRepository
     */
    private $suiteRepository;

    /**
     * @var SpecificationFinder
     */
    private $specificationFinder;

    /**
     * @var array
     */
    private $suiteGroups = [];

    /**
     * @param SuiteRepository $suiteRepository
     * @param SpecificationFinder $specificationFinder
     * @param array $suiteGroups
     */
    public function __construct(
        SuiteRepository $suiteRepository,
        SpecificationFinder $specificationFinder,
        array $suiteGroups
    ) {
        $this->suiteRepository = $suiteRepository;
        $this->specificationFinder = $specificationFinder;
        $this->suiteGroups = $suiteGroups;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--available-suites-group',
                null,
                InputOption::VALUE_REQUIRED,
                'Show all available test suites in group.'.PHP_EOL.
                'There is special group "'.self::UNGROUPED_GROUP.'" that contains all ungrouped suites'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $suiteGroup = $input->getOption('available-suites-group');

        if (!$suiteGroup) {
            return;
        }

        if (self::UNGROUPED_GROUP === $suiteGroup) {
            $this->groupUngrouped();
        }

        if (!isset($this->suiteGroups[$suiteGroup])) {
            throw new SuiteNotFoundException(sprintf(
                '`%s` suite group is not found or has not been properly registered.',
                $suiteGroup
            ), $suiteGroup);
        }

        foreach ($this->suiteRepository->getSuites() as $suite) {
            if (!in_array($suite->getName(), $this->suiteGroups[$suiteGroup])) {
                continue;
            }

            $iterators = $this->specificationFinder->findSuitesSpecifications([$suite]);
            $countFeatures = array_sum(array_map('iterator_count', $iterators));

            if (0 !== $countFeatures) {
                $output->writeln($suite->getName());
            }
        }

        return 0;
    }

    private function groupUngrouped()
    {
        $groupedSuites = array_unique(array_reduce($this->suiteGroups, 'array_merge', []));
        $this->suiteGroups[self::UNGROUPED_GROUP] = [];

        foreach ($this->suiteRepository->getSuites() as $suite) {
            if (in_array($suite->getName(), $groupedSuites)) {
                continue;
            }

            $this->suiteGroups[self::UNGROUPED_GROUP][] = $suite->getName();
        };
    }
}
