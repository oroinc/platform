<?php

namespace Oro\Bundle\ActivityBundle\Autocomplete;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\DBAL\Types\Type;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Event\SearchAliasesEvent;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;

/**
 * This is specified handler that search targets entities for specified activity class.
 *
 * Can not use default Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface cause in this handler we manipulate
 * with different types of entities.
 *
 * Also @see Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer
 */
class ContextSearchHandler implements ConverterInterface
{
    /** @var TokenStorageInterface */
    protected $token;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var Indexer */
    protected $indexer;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /** @var ObjectManager */
    protected $objectManager;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var string */
    protected $class;

    /**
     * @param TokenStorageInterface    $token
     * @param TranslatorInterface      $translator
     * @param Indexer                  $indexer
     * @param ActivityManager          $activityManager
     * @param ConfigManager            $configManager
     * @param EntityClassNameHelper    $entityClassNameHelper
     * @param ObjectManager            $objectManager
     * @param ObjectMapper             $mapper
     * @param EventDispatcherInterface $dispatcher
     * @param string|null              $class
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TokenStorageInterface $token,
        TranslatorInterface $translator,
        Indexer $indexer,
        ActivityManager $activityManager,
        ConfigManager $configManager,
        EntityClassNameHelper $entityClassNameHelper,
        ObjectManager $objectManager,
        ObjectMapper $mapper,
        EventDispatcherInterface $dispatcher,
        $class = null
    ) {
        $this->token                 = $token;
        $this->translator            = $translator;
        $this->indexer               = $indexer;
        $this->activityManager       = $activityManager;
        $this->configManager         = $configManager;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->objectManager         = $objectManager;
        $this->mapper                = $mapper;
        $this->dispatcher            = $dispatcher;
        $this->class                 = $class;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        if ($searchById) {
            return $this->searchById($query);
        }

        $page        = max((int)$page, 1);
        $perPage     = (int)$perPage > 0 ? (int)$perPage : 10;
        $firstResult = ($page - 1) * $perPage;
        $perPage++;

        $items = [];
        $from  = $this->getSearchAliases();
        if ($from) {
            $items = $this->indexer->simpleSearch(
                $query,
                $firstResult,
                $perPage,
                $from,
                $page
            )->getElements();
        }

        $hasMore = count($items) === $perPage;
        if ($hasMore) {
            $items = array_slice($items, 0, $perPage - 1);
        }

        return [
            'results' => $this->convertItems($items),
            'more'    => $hasMore
        ];
    }

    /**
     * Search by json string with targets class names and ids
     *
     * @param string $targetsString
     *
     * @return array
     */
    protected function searchById($targetsString)
    {
        $targets        = $this->decodeTargets($targetsString);
        $groupedTargets = $this->groupTargetsByEntityClasses($targets);
        $queryBuilder   = $this->getAssociatedTargetEntitiesQueryBuilder($groupedTargets);
        $result         = $queryBuilder->getQuery()->getResult();
        $items          = [];

        foreach ($result as $target) {
            $items[] = new Item(
                $this->objectManager,
                $target['entity'],
                $target['id'],
                $target['title']
            );
        }

        return [
            'results' => $this->convertItems($items),
            'more'    => false
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $this->dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));

        /** @var Item $item */
        $text      = $item->getRecordTitle();
        $className = $item->getEntityName();

        if (strlen(trim($text)) === 0) {
            $text = $this->translator->trans('oro.entity.item', ['%id%' => $item->getRecordId()]);
        }

        if ($label = $this->getClassLabel($className)) {
            $text .= ' (' . $label . ')';
        }

        return [
            'id'   => json_encode(
                [
                    'entityClass' => $className,
                    'entityId'    => $item->getRecordId(),
                ]
            ),
            'text' => $text
        ];
    }

    /**
     * @param Item[] $items
     *
     * @return array
     */
    protected function convertItems(array $items)
    {
        $user = $this->token->getToken()->getUser();

        $result = [];
        /** @var Item $item */
        foreach ($items as $item) {
            // Exclude current user from result
            if (ClassUtils::getClass($user) === $item->getEntityName() && $user->getId() === $item->getRecordId()) {
                continue;
            }

            $result[] = $this->convertItem($item);
        }

        return $result;
    }

    /**
     * Decodes targets json query string and returns targets array
     *
     * @param  $targetsString
     *
     * @return array
     */
    protected function decodeTargets($targetsString)
    {
        $targetsJsonArray = explode(';', $targetsString);
        $targetsArray = [];

        foreach ($targetsJsonArray as $targetJson) {
            if (!$targetJson) {
                continue;
            }

            $target = json_decode($targetJson, true);

            if (!isset($target['entityClass']) || !$target['entityClass']
                || !isset($target['entityId']) || !$target['entityId']
            ) {
                continue;
            }

            $targetsArray[] = $target;
        }

        return $targetsArray;
    }

    /**
     * Groups linear array of targets to array with key as entity class and
     * value as array of targets ids
     *
     * @param array $targetsArray
     *
     * @return array
     */
    protected function groupTargetsByEntityClasses(array $targetsArray)
    {
        $result = [];

        foreach ($targetsArray as $target) {
            if (!isset($result[$target['entityClass']])) {
                $result[$target['entityClass']] = [];
            }

            $result[$target['entityClass']][] = $target['entityId'];
        }

        return $result;
    }

    /**
     * Returns a DQL expression that can be used to get a text representation of the given type of entities.
     *
     * @param string $className The FQCN of the entity
     * @param string $alias     The alias in SELECT or JOIN statement
     *
     * @return string|false
     */
    protected function getNameDQL($className, $alias)
    {
        $fields = $this->mapper->getEntityMapParameter($className, 'title_fields');
        if ($fields) {
            $titleParts = [];
            foreach ($fields as $field) {
                $titleParts[] = $alias . '.' . $field;
                $titleParts[] = '\' \'';
            }

            return QueryUtils::buildConcatExpr($titleParts);
        }

        return false;
    }

    /**
     * Query builder to get target entities in a single query
     *
     * @param array $groupedTargets
     *
     * @return SqlQueryBuilder
     * @throws \Doctrine\ORM\Query\QueryException
     */
    protected function getAssociatedTargetEntitiesQueryBuilder(array $groupedTargets)
    {
        /** @var EntityManager $objectManager */
        $objectManager = $this->objectManager;

        $selectStmt = null;
        $subQueries = [];
        foreach ($groupedTargets as $entityClass => $ids) {
            $nameExpr = $this->getNameDQL($entityClass, 'e');
            /** @var QueryBuilder $subQb */
            $subQb    = $objectManager->getRepository($entityClass)->createQueryBuilder('e')
                ->select(
                    sprintf(
                        'e.id AS id, \'%s\' AS entityClass, ' . ($nameExpr ?: '\'\'') . ' AS entityTitle',
                        str_replace('\'', '\'\'', $entityClass)
                    )
                );
            $subQb->where(
                $subQb->expr()->in('e.id', $ids)
            );

            $subQuery     = $subQb->getQuery();
            $subQueries[] = QueryUtils::getExecutableSql($subQuery);

            if (empty($selectStmt)) {
                $mapping    = QueryUtils::parseQuery($subQuery)->getResultSetMapping();
                $selectStmt = sprintf(
                    'entity.%s AS id, entity.%s AS entity, entity.%s AS title',
                    QueryUtils::getColumnNameByAlias($mapping, 'id'),
                    QueryUtils::getColumnNameByAlias($mapping, 'entityClass'),
                    QueryUtils::getColumnNameByAlias($mapping, 'entityTitle')
                );
            }
        }

        $rsm = QueryUtils::createResultSetMapping($objectManager->getConnection()->getDatabasePlatform());
        $rsm
            ->addScalarResult('id', 'id', Type::INTEGER)
            ->addScalarResult('entity', 'entity')
            ->addScalarResult('title', 'title');

        $queryBuilder = new SqlQueryBuilder($objectManager, $rsm);
        $queryBuilder
            ->select($selectStmt)
            ->from('(' . implode(' UNION ALL ', $subQueries) . ')', 'entity');

        return $queryBuilder;
    }

    /**
     * Gets label for the class
     *
     * @param string $className - FQCN
     *
     * @return string|null
     */
    protected function getClassLabel($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        $label = $this->configManager->getProvider('entity')->getConfig($className)->get('label');

        return $this->translator->trans($label);
    }

    /**
     * Get search aliases for all entities which can be associated with specified activity.
     *
     * @return string[]
     */
    protected function getSearchAliases()
    {
        $class               = $this->entityClassNameHelper->resolveEntityClass($this->class, true);
        $aliases             = [];
        $targetEntityClasses = array_keys($this->activityManager->getActivityTargets($class));

        foreach ($targetEntityClasses as $targetEntityClass) {
            $alias = $this->indexer->getEntityAlias($targetEntityClass);
            if (null !== $alias) {
                $aliases[] = $alias;
            }
        }
        /** dispatch oro_activity.search_aliases event */
        $event = new SearchAliasesEvent($aliases, $targetEntityClasses);
        $this->dispatcher->dispatch(SearchAliasesEvent::EVENT_NAME, $event);
        $aliases = $event->getAliases();

        return $aliases;
    }
}
