<?php

namespace Oro\Bundle\LocaleBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Change value for `importexport.excluded` for all AbstractLocalizedFallbackValue#fallback fields.
 */
class UpdateFallbackExcludedQuery extends ParametrizedMigrationQuery
{
    private bool $value = false;

    public function __construct(bool $value = false)
    {
        $this->value = $value;
    }

    public function getDescription()
    {
        $messages = [];

        foreach ($this->getQueries() as $query) {
            $query->setConnection($this->connection);

            $messages = array_merge($messages, $query->getDescription());
        }

        return $messages;
    }

    public function execute(LoggerInterface $logger)
    {
        foreach ($this->getQueries() as $query) {
            $query->setConnection($this->connection);
            $query->execute($logger);
        }
    }

    /**
     * @return UpdateEntityConfigFieldValueQuery[]
     */
    private function getQueries(): iterable
    {
        $qb = $this->connection->createQueryBuilder();
        $rows = $qb
            ->select('e.class_name')
            ->from('oro_entity_config_field', 'f')
            ->leftJoin('f', 'oro_entity_config', 'e', 'f.entity_id = e.id')
            ->where($qb->expr()->eq('f.field_name', ':field_name'))
            ->setParameter('field_name', 'fallback')
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $class = $row['class_name'];

            if (!is_a($class, AbstractLocalizedFallbackValue::class, true)) {
                continue;
            }

            yield new UpdateEntityConfigFieldValueQuery($class, 'fallback', 'importexport', 'excluded', $this->value);
        }
    }
}
