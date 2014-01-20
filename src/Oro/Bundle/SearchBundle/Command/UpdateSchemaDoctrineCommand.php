<?php

namespace Oro\Bundle\SearchBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand
    as BaseUpdateSchemaDoctrineCommand;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSchemaDoctrineCommand extends BaseUpdateSchemaDoctrineCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $arguments  = array('');
        $indexInput = new ArrayInput($arguments);
        $command    = $this->getApplication()->find(
            AddFulltextIndexesCommand::COMMAND_NAME
        );
        $returnCode = $command->run($indexInput, $output);

        if ($returnCode == 0) {
            $output->writeln('Schema update and create index completed');
        }
    }
}
