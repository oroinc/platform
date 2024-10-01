<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides a DQL expression for an entity field that should be used in WHERE and ORDER BY clauses.
 */
class FieldDqlExpressionProvider implements FieldDqlExpressionProviderInterface
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function getFieldDqlExpression(QueryBuilder $qb, string $fieldPath): ?string
    {
        $pathDelimiterPos = strpos($fieldPath, '.');
        if (false === $pathDelimiterPos) {
            return null;
        }

        $alias = substr($fieldPath, 0, $pathDelimiterPos);
        $entityClass = QueryBuilderUtil::findClassByAlias($qb, $alias);
        if (!$entityClass) {
            return null;
        }

        $fieldName = substr($fieldPath, $pathDelimiterPos + 1);
        if (!$this->configManager->hasConfig($entityClass, $fieldName)) {
            return null;
        }
        $fieldType = $this->configManager->getId('extend', $entityClass, $fieldName)->getFieldType();
        if (ExtendHelper::isSingleEnumType($fieldType)) {
            return QueryBuilderUtil::sprintf("JSON_EXTRACT(%s.serialized_data, '%s')", $alias, $fieldName);
        }
        if (ExtendHelper::isMultiEnumType($fieldType)) {
            QueryBuilderUtil::checkIdentifier($alias);
            QueryBuilderUtil::checkIdentifier($fieldName);

            return sprintf(
                "JSONB_ARRAY_CONTAINS_JSON(%s.serialized_data, '%s', CONCAT('\"', {entity:%s}.id, '\"')) = true",
                $alias,
                $fieldName,
                EnumOption::class
            );
        }

        return null;
    }
}
