<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli;

use Behat\Testwork\Cli\Controller;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\StatisticModelInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSchemaController implements Controller
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * List of model namespaces
     * @var StatisticModelInterface[]
     */
    private $models =[];

    /**
     * @param Connection $connection
     * @param array $models
     */
    public function __construct(Connection $connection, $models = [])
    {
        $this->connection = $connection;
        $this->models = $models;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--update-statistic-schema',
                null,
                InputOption::VALUE_NONE,
                'Update statistic database schema and exit'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('update-statistic-schema')) {
            return;
        }

        $this->connection->ping();
        $schema = new Schema();

        foreach ($this->models as $model) {
            $model::declareSchema($schema);
        }

        $currentSchema = $this->connection->getSchemaManager()->createSchema();

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($currentSchema, $schema);

        $queries = $schemaDiff->toSql($this->connection->getDatabasePlatform());

        foreach ($queries as $query) {
            $this->connection->query($query);
        }

        $this->connection->close();

        $output->writeln('Schema was updated successfully');

        return 0;
    }
}
