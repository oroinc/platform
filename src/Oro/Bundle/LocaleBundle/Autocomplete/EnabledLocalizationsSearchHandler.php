<?php

namespace Oro\Bundle\LocaleBundle\Autocomplete;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;

/**
 * The autocomplete handler to search enabled localizations by scope.
 */
class EnabledLocalizationsSearchHandler extends SearchHandler
{
    const DELIMITER = ';';

    /**
     * @var ConfigManager
     */
    protected $configManager;

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

        [$searchTerm, $scope] = explode(static::DELIMITER, $search, 2);
        $entityIds = $this->searchIdsByTermAndWebsite($searchTerm, $firstResult, $maxResults, $scope);
        if (!count($entityIds)) {
            return [];
        }

        $queryBuilder = $this->entityRepository->createQueryBuilder('l');
        $queryBuilder->where($queryBuilder->expr()->in('l.' . $this->idFieldName, ':entityIds'));
        $queryBuilder->setParameter('entityIds', $entityIds);
        $query = $this->aclHelper->apply($queryBuilder);

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
        $enabledLocalizationIds = $this->getEnabledLocalizationsByScope($scope);

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

    private function getEnabledLocalizationsByScope(string $scope): array
    {
        return (array) $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
            false,
            false,
            $scope ? (int) $scope : null
        );
    }

    /**
     * Overwrites parent method mo make grid search work correct considering new delimiter ";" which divides
     * localization id and website
     * {@inheritdoc}
     */
    protected function findById($query): array
    {
        //Explodes query string - "1;2". Where 1 - is localization id, and 2 - scope id
        //By ";" - delimiter. Calling this method assumes we search only for ONE entity
        [$searchId, $scope] = explode(static::DELIMITER, $query, 2);

        //Get enabled localizations for current scope (website id)
        $enabledLocalizationIds = $this->getEnabledLocalizationsByScope($scope);

        //Check if searched id does not exist in current configuration no need to query
        if (!\in_array($searchId, $enabledLocalizationIds)) {
            return [];
        }

        return $this->getEntitiesByIds([$searchId]);
    }
}
