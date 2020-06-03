<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The CLI command to update the database and all related caches to reflect changes made in extended entities.
 */
class UpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-extend:update';

    /** @var EntityExtendUpdateProcessor */
    private $entityExtendUpdateProcessor;

    /**
     * @param EntityExtendUpdateProcessor $entityExtendUpdateProcessor
     */
    public function __construct(EntityExtendUpdateProcessor $entityExtendUpdateProcessor)
    {
        parent::__construct();
        $this->entityExtendUpdateProcessor = $entityExtendUpdateProcessor;
    }

    /**
     * {@inheritDoc}
     */
    public function configure()
    {
        $this->setDescription(
            'Updates the database and all related caches to reflect changes made in extended entities.'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Updating the database and all entity extend related caches ...</comment>');

        if (!$this->entityExtendUpdateProcessor->processUpdate()) {
            $output->writeln('<error>The update failed.</error>');

            return 1;
        }

        $output->writeln('<info>The update complete.</info>');

        return 0;
    }
}
