<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * The ORM data hydrator used by {@see DynamicTranslationLoader::loadTranslations}.
 */
class DynamicTranslationHydrator extends AbstractHydrator
{
    private const LOCALE = 'locale';
    private const DOMAIN = 'domain';
    private const KEY = 'key';
    private const VALUE = 'value';

    /**
     * {@inheritDoc}
     */
    protected function hydrateAllData()
    {
        $fields = array_flip($this->_rsm->scalarMappings);
        $valueType = Type::getType($this->_rsm->typeMappings[$fields[self::VALUE]]);

        $result = [];
        $stmt = $this->statement();
        while (true) {
            $row = $stmt->fetchAssociative();
            if (false === $row) {
                break;
            }
            $result[$row[$fields[self::LOCALE]]][$row[$fields[self::DOMAIN]]][$row[$fields[self::KEY]]] =
                $valueType->convertToPHPValue($row[$fields[self::VALUE]], $this->_platform);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function toIterable($stmt, ResultSetMapping $resultSetMapping, array $hints = []): iterable
    {
        throw new \BadMethodCallException('not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function hydrateRow()
    {
        throw new \BadMethodCallException('not supported');
    }
}
