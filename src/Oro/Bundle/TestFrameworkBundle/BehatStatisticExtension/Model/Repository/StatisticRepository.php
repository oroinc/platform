<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\StatisticModelInterface;

class StatisticRepository implements StatisticRepositoryInterface, ObjectRepository
{
    const MAX_LIMIT = 1000;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var StatisticModelInterface[]
     */
    protected $collection = [];

    /**
     * @var StatisticModelInterface
     */
    protected $className;

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

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $orderBy = $orderBy ?: ['id', 'DESC'];
        $limit = $limit ?: self::MAX_LIMIT;

        if (empty($criteria)) {
            throw new \RuntimeException('It\'s prohibited call "findBy" on statistics without criteria');
        }

        if ($limit > self::MAX_LIMIT) {
            throw new \RuntimeException(sprintf('You should not set limit over then %s', self::MAX_LIMIT));
        }

        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->className::getName())
        ;

        $andExpr = $queryBuilder->expr()->andX();

        foreach ($criteria as $field => $value) {
            $filedKey = uniqid(':where_field_');
            $valueKey = uniqid(':where_value_');
            $andExpr->add($queryBuilder->expr()->eq($filedKey, $valueKey));
            $queryBuilder->setParameter($filedKey, $field);
            $queryBuilder->setParameter($valueKey, $value);
        }

        $queryBuilder->where($andExpr)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy($orderBy[0], $orderBy[1])
        ;

        $result = $queryBuilder->execute()
            ->fetchAll()
        ;

        $models = array_map(function (array $data) {
            $model = $this->className::fromArray($data);
            $this->collection[$model->getId()] = $model;

            return $model;
        }, $result);

        return $models;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        if (isset($this->collection[$id])) {
            return $this->collection[$id];
        }

        $result = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->className::getName())
            ->where($this->className::getIdField().' = ?')
            ->setParameter(0, $id)
            ->orderBy('id', 'DESC')
            ->execute()
            ->fetch()
        ;

        if (!$result) {
            return new $this->className;
        }

        $model = $this->className::fromArray($result);
        $this->collection[$model->getId()] = $model;

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        throw new \RuntimeException('It\'s prohibited call "findAll" on statistics. Use "findBy" method');
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }
}
