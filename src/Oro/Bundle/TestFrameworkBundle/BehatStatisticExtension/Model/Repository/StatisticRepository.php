<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\Repository;

use Doctrine\DBAL\Connection;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\StatisticModelInterface;

class StatisticRepository implements StatisticRepositoryInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var StatisticModelInterface[]
     */
    protected $collection;

    /**
     * StatisticRepository constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function add(StatisticModelInterface $model)
    {
        $this->collection[] = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->connection->connect();

        foreach ($this->collection as $model) {
            if (!$model->isNew()) {
                continue;
            }

            $this->connection->insert($model::getName(), $model->toArray());
        }

        $this->connection->close();
    }
}
