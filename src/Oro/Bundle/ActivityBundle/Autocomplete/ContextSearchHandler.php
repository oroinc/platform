<?php

namespace Oro\Bundle\ActivityBundle\Autocomplete;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityBundle\Event\SearchAliasesEvent;
use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

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

    /** @var EntityNameResolver */
    protected $nameResolver;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var string */
    protected $class;

    /**
     * @param TranslatorInterface      $translator
     * @param Indexer                  $indexer
     * @param ActivityManager          $activityManager
     * @param ConfigManager            $configManager
     * @param EntityClassNameHelper    $entityClassNameHelper
     * @param ObjectManager            $objectManager
     * @param EntityNameResolver       $nameResolver
     * @param EventDispatcherInterface $dispatcher
     * @param string|null              $class
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TranslatorInterface $translator,
        Indexer $indexer,
        ActivityManager $activityManager,
        ConfigManager $configManager,
        EntityClassNameHelper $entityClassNameHelper,
        ObjectManager $objectManager,
        EntityNameResolver $nameResolver,
        EventDispatcherInterface $dispatcher,
        $class = null
    ) {
        $this->translator            = $translator;
        $this->indexer               = $indexer;
        $this->activityManager       = $activityManager;
        $this->configManager         = $configManager;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->objectManager         = $objectManager;
        $this->nameResolver          = $nameResolver;
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
        $result = [];
        /** @var Item $item */
        foreach ($items as $item) {
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
        $targetsJsonArray = explode(ContextsToViewTransformer::SEPARATOR, $targetsString);
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
     * Query builder to get target entities in a single query
     *
     * @param array $groupedTargets
     *
     * @return SqlQueryBuilder
     * @throws \Doctrine\ORM\Query\QueryException
     */
    protected function getAssociatedTargetEntitiesQueryBuilder(array $groupedTargets)
    {
        /** @var EntityManager $em */
        $em = $this->objectManager;

        $qb = new UnionQueryBuilder($em);
        $qb
            ->addSelect('id', 'id', Type::INTEGER)
            ->addSelect('entityClass', 'entity')
            ->addSelect('entityTitle', 'title');
        foreach ($groupedTargets as $entityClass => $ids) {
            $nameDql = $this->nameResolver->prepareNameDQL(
                $this->nameResolver->getNameDQL($entityClass, 'e'),
                true
            );
            $subQb = $em->getRepository($entityClass)->createQueryBuilder('e');
            $subQb
                ->select(
                    'e.id AS id',
                    (string)$subQb->expr()->literal($entityClass) . ' AS entityClass',
                    $nameDql . ' AS entityTitle'
                );
            $subQb->where($subQb->expr()->in('e.id', $ids));
            $qb->addSubQuery($subQb->getQuery());
        }

        return $qb->getQueryBuilder();
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
