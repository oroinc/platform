<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityExtendBundle\Tools\EntityEnumOptionsActualizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Actualize removed or is not actual enum option ids for target entities command.
 */
class ActualizeEntityEnumOptionsCommand extends Command
{
    protected static $defaultName = 'oro:entity-extend:actualize:enum-options';

    public function __construct(private EntityEnumOptionsActualizer $entityEnumOptionsActualizer)
    {
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function configure(): void
    {
        $this->setHidden()
            ->addOption('enum-code', null, InputOption::VALUE_REQUIRED, 'Enum code')
            ->addOption('enum-option-id', null, InputOption::VALUE_REQUIRED, 'Enum option id')
            ->setDescription('Actualize removed or is not actual entity enum options.');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->entityEnumOptionsActualizer->run(
            $input->getOption('enum-code'),
            $input->getOption('enum-option-id')
        );

        return Command::SUCCESS;
    }
}
