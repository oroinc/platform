<?php
declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Command;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists Doctrine listeners that can be disabled.
 */
class OptionalListenersCommand extends Command
{
    protected static $defaultName = 'oro:platform:optional-listeners';

    private OptionalListenerManager $optionalListenerManager;

    public function __construct(OptionalListenerManager $optionalListenerManager)
    {
        $this->optionalListenerManager = $optionalListenerManager;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Lists Doctrine listeners that can be disabled.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command lists optional Doctrine listeners
that can be disabled when running console commands by using
the <comment>--disabled-listeners</comment> option.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('List of optional listeners:');
        foreach ($this->optionalListenerManager->getListeners() as $listener) {
            $output->writeln(sprintf('  <comment>> %s</comment>', $listener));
        }

        return 0;
    }
}
