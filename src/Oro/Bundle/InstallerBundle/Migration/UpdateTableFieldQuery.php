<?php

namespace Oro\Bundle\InstallerBundle\Migration;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateTableFieldQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $to;

    /**
     * @var string
     */
    private $columnId;

    /**
     * @param string $table
     * @param string $column
     * @param string $from
     * @param string $to
     * @param string $to
     */
    public function __construct($table, $column, $from, $to, $columnId = 'id')
    {
        $this->table = $table;
        $this->column = $column;
        $this->from = $from;
        $this->to = $to;
        $this->columnId = $columnId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->processQueries($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $table = $this->table;
        $column = $this->column;
        $from = $this->from;
        $to = $this->to;
        $columnId = $this->columnId;

        $preparedFrom = str_replace('\\', '\\\\', $from);
        $rows = $this->connection
            ->fetchAll("SELECT $columnId, $column FROM $table WHERE $column LIKE '%$preparedFrom%'");
        foreach ($rows as $row) {
            $id = $row[$columnId];
            $originalValue = $row[$column];
            $alteredValue = $this->replaceStringValue($originalValue, $from, $to);
            if ($alteredValue !== $originalValue) {
                $this->connection
                    ->executeQuery("UPDATE $table SET $column = ? WHERE $columnId = ?", [$alteredValue, $id]);
            }
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function replaceArrayValue(array $data, $from, $to)
    {
        foreach ($data as $originalKey => $value) {
            $key = $this->replaceStringValue($originalKey, $from, $to);
            if ($key !== $originalKey) {
                unset($data[$originalKey]);
                $data[$key] = $value;
            }
            if (is_array($value)) {
                $data[$key] = $this->replaceArrayValue($value, $from, $to);
            } elseif (is_string($value)) {
                $data[$key] = $this->replaceStringValue($value, $from, $to);
            } elseif ($value instanceof ConfigIdInterface) {
                $originalClass = $value->getClassName();
                $alteredClass = $this->replaceStringValue($originalClass, $from, $to);
                if ($alteredClass !== $originalClass) {
                    $reflectionProperty = new \ReflectionProperty(get_class($value), 'className');
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($value, $alteredClass);
                }
            }
        }

        return $data;
    }

    /**
     * @param string $value
     * @param string $from
     * @param string $to
     * @return string
     */
    protected function replaceStringValue($value, $from, $to)
    {
        if (!is_string($value)) {
            return $value;
        }

        return str_replace([$from], [$to], $value);
    }
}
