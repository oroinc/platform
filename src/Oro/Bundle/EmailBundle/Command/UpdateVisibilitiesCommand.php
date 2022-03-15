<?php
declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Updates visibilities for emails and email addresses.
 */
class UpdateVisibilitiesCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:email:update-visibilities';

    private MessageProducerInterface $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        parent::__construct();

        $this->producer = $producer;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Updates visibilities for emails and email addresses.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates visibilities for emails and email addresses.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->producer->send(UpdateVisibilitiesTopic::getName(), []);

        $io->success('Update of visibilities has been scheduled.');

        return 0;
    }
}
