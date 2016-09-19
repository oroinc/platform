<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm\Configs;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryConverter\YamlConverter;
use Oro\Bundle\DataGridBundle\Exception\DatasourceException;

class YamlProcessor implements ConfigProcessorInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;

    }

    /**
     * {@inheritdoc}
     */
    public function processQuery(array $config)
    {
        if (array_key_exists('query', $config)) {
            $queryConfig = $config['query'];
            $converter = new YamlConverter();
            return $converter->parse(['query' => $queryConfig], $this->registry);

        } elseif (array_key_exists('entity', $config) && array_key_exists('repository_method', $config)) {
            $entity = $config['entity'];
            $method = $config['repository_method'];
            $repository = $this->registry->getRepository($entity);
            if (method_exists($repository, $method)) {
                $qb = $repository->$method();
                if ($qb instanceof QueryBuilder) {
                    return $qb;
                } else {
                    throw new DatasourceException(
                        sprintf(
                            '%s::%s() must return an instance of Doctrine\ORM\QueryBuilder, %s given',
                            get_class($repository),
                            $method,
                            is_object($qb) ? get_class($qb) : gettype($qb)
                        )
                    );
                }
            } else {
                throw new DatasourceException(sprintf('%s has no method %s', get_class($repository), $method));
            }

        } else {
            throw new DatasourceException(get_class($this).' expects to be configured with query or repository method');
        }
    }

    /**
     * Creates QueryBuilder for count query if configs for count query exist in configs array.
     * Merges
     * {@inheritdoc}
     */
    public function processCountQuery(array $config)
    {
        if (array_key_exists('count_query', $config) && is_array($config['count_query'])) {
            $queryConfig = $config['count_query'];
            if (array_key_exists('query', $config) && is_array($config['query'])) {
                $queryConfig = $this->mergeQueryConfigs($config);
            }
            $converter = new YamlConverter();

            return $converter->parse(['query' => $queryConfig], $this->registry);
        }

        return null;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function mergeQueryConfigs(array $config)
    {
        return array_merge($config['query'], $config['count_query']);
    }
}
