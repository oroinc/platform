<?php
declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailAssociationsTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates email associations.
 */
class UpdateAssociationsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:email:update-associations';

    private MessageProducerInterface $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        parent::__construct();

        $this->producer = $producer;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Updates email associations.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates email associations.

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
        $this->producer->send(UpdateEmailAssociationsTopic::getName(), []);

        $output->writeln('<info>Update of associations has been scheduled.</info>');

        return 0;
    }
}
