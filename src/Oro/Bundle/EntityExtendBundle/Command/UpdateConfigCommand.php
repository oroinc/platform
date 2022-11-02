<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter\ByInitialStateFilter;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates extend entity config.
 */
class UpdateConfigCommand extends Command
{
    protected static $defaultName = 'oro:entity-extend:update-config';

    private ExtendConfigDumper $extendConfigDumper;

    public function __construct(ExtendConfigDumper $extendConfigDumper)
    {
        $this->extendConfigDumper = $extendConfigDumper;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption(
                'update-custom',
                null,
                InputOption::VALUE_NONE,
                'Apply user changes that require schema update'
            )
            ->addOption(
                'initial-state-path',
                null,
                InputOption::VALUE_OPTIONAL,
                'File containing the initial state of entity configs'
            )
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force the execution')
            ->setDescription('Updates extend entity config.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates extend entity config.

  <info>php %command.full_name%</info>
  
<error>This is an internal command. Please do not run it manually.</error>
<error>Execution of this command can break the system.</error>

Use the <info>--force</info> option to force the execution.

Use the <info>--update-custom</info> option to apply user changes that require database schema update:

  <info>php %command.full_name% --force --update-custom</info>

The <info>--initial-state-path</info> option can be used to provide a path to the file
that contains the initial state of the entity configs:

  <info>php %command.full_name% --force --initial-state-path=<file-path></info>

HELP
            )
            ->addUsage('--force --update-custom')
            ->addUsage('--force --initial-state-path=<file-path>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getDescription());

        $force = $input->getOption('force');
        if (!$force) {
            $output->writeln('<error>This is an internal command. Please do not run it manually.</error>');
            $output->writeln('<error>Execution of this command can break the system.</error>');

            return 1;
        }

        $this->extendConfigDumper->updateConfig($this->getFilter($input), $input->getOption('update-custom'));

        return 0;
    }

    protected function getFilter(InputInterface $input): ?callable
    {
        $filter = null;

        $initialStatePath = $input->getOption('initial-state-path');
        if (!empty($initialStatePath)) {
            $initialStates = unserialize(file_get_contents($initialStatePath));
            if (!empty($initialStates)) {
                $filter = new ByInitialStateFilter($initialStates);
            }
        }

        return $filter;
    }
}
