<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerInterface;

abstract class ParametrizedMigrationQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Adds a query to a log
     *
     * @param LoggerInterface $logger
     * @param string          $query
     * @param array           $params
     * @param array           $types
     */
    protected function logQuery(LoggerInterface $logger, $query, array $params = [], array $types = [])
    {
        $logger->notice($query);
        if (!empty($params)) {
            $resolvedParams = $this->resolveParams($params, $types);
            $logger->notice('Parameters:');
            foreach ($resolvedParams as $key => $val) {
                if (is_array($val)) {
                    $val = implode(',', $val);
                }
                $logger->notice(sprintf('[%s] = %s', $key, $val));
            }
        }
    }

    /**
     * Resolves the parameters to a format which can be displayed.
     *
     * @param array $params
     * @param array $types
     *
     * @return array
     */
    protected function resolveParams(array $params, array $types)
    {
        $resolvedParams = array();

        // Check whether parameters are positional or named. Mixing is not allowed.
        if (is_int(key($params))) {
            // Positional parameters
            $typeOffset = array_key_exists(0, $types) ? -1 : 0;
            $bindIndex  = 1;
            foreach ($params as $value) {
                $typeIndex = $bindIndex + $typeOffset;
                if (isset($types[$typeIndex])) {
                    $type                       = $types[$typeIndex];
                    $value                      = $this->convertToDatabaseValue($value, $type);
                    $resolvedParams[$bindIndex] = $value;
                } else {
                    $resolvedParams[$bindIndex] = $value;
                }
                $bindIndex++;
            }
        } else {
            // Named parameters
            foreach ($params as $name => $value) {
                if (isset($types[$name])) {
                    $type                  = $types[$name];
                    $value                 = $this->convertToDatabaseValue($value, $type);
                    $resolvedParams[$name] = $value;
                } else {
                    $resolvedParams[$name] = $value;
                }
            }
        }

        return $resolvedParams;
    }

    /**
     * Converts a value from its PHP representation to its database representation.
     *
     * @param mixed       $value
     * @param string|Type $type
     *
     * @return array the (escaped) value
     */
    protected function convertToDatabaseValue($value, $type)
    {
        if (is_string($type)) {
            $type = Type::getType($type);
        }
        if ($type instanceof Type) {
            $value = $type->convertToDatabaseValue($value, $this->connection->getDatabasePlatform());
        }

        return $value;
    }
}
