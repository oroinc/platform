<?php

namespace Oro\Bundle\LocaleBundle\Autocomplete;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;

/**
 * This handler provide methods to search enabled localizations by scope.
 */
class EnabledLocalizationsSearchHandler extends SearchHandler
{
    const DELIMITER = ';';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param string $entityName
     * @param array $properties
     * @param ConfigManager $configManager
     */
    public function __construct(
        string $entityName,
        array $properties,
        ConfigManager $configManager
    ) {
        parent::__construct($entityName, $properties);
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        if (false === strpos($search, static::DELIMITER)) {
            return [];
        }

        list($searchTerm, $scope) = explode(static::DELIMITER, $search, 2);
        $entityIds = $this->searchIdsByTermAndWebsite($searchTerm, $firstResult, $maxResults, $scope);
        if (!count($entityIds)) {
            return [];
        }

        $queryBuilder = $this->entityRepository->createQueryBuilder('l');
        $queryBuilder->where($queryBuilder->expr()->in('l.' . $this->idFieldName, ':entityIds'));
        $queryBuilder->setParameter('entityIds', $entityIds);
        $query = $this->aclHelper->apply($queryBuilder, 'VIEW');

        return $query->getResult();
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     * @param mixed|null $scope
     *
     * @return array
     */
    private function searchIdsByTermAndWebsite(
        string $search,
        int $firstResult,
        int $maxResults,
        $scope = null
    ) {
        $enabledLocalizationIds = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
            [],
            false,
            $scope ? (int) $scope : null
        );

        $query = $this->indexer->getSimpleSearchQuery($search, $firstResult, $maxResults, $this->entitySearchAlias);
        $query->getCriteria()->andWhere(Criteria::expr()->in(
            Criteria::implodeFieldTypeName(Query::TYPE_INTEGER, 'id'),
            $enabledLocalizationIds
        ));

        $result = $this->indexer->query($query);

        return array_map(
            function (Item $element) {
                return $element->getRecordId();
            },
            $result->getElements()
        );
    }
}
