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

class AvailableFeaturesController implements Controller
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
                '--available-features',
                null,
                InputOption::VALUE_NONE,
                'Output available registered features'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('available-features')) {
            return;
        }

        foreach ($this->suiteRepository->getSuites() as $suite) {
            $iterators = $this->specificationFinder->findSuitesSpecifications([$suite]);
            foreach ($iterators as $iterator) {
                /** @var FeatureNode $item */
                foreach ($iterator as $item) {
                    $output->writeln($item->getFile());
                }
            }
        }

        return 0;
    }
}
