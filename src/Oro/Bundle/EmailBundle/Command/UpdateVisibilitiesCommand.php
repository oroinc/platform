<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Updates visibilities for emails and email addresses.
 */
#[AsCommand(
    name: 'oro:email:update-visibilities',
    description: 'Updates visibilities for emails and email addresses.'
)]
class UpdateVisibilitiesCommand extends Command
{
    private MessageProducerInterface $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        parent::__construct();

        $this->producer = $producer;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates visibilities for emails and email addresses.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->producer->send(UpdateVisibilitiesTopic::getName(), []);

        $io->success('Update of visibilities has been scheduled.');

        return Command::SUCCESS;
    }
}
