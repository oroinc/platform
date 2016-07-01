<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\SearchBundle\Query\Expression\Lexer;
use Oro\Bundle\SearchBundle\Query\Expression\Parser as ExpressionParser;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Security\SecurityProvider;

use Oro\Bundle\SecurityBundle\Search\AclHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Indexer
{
    const TEXT_ALL_DATA_FIELD   = 'all_text';

    const RELATION_ONE_TO_ONE   = 'one-to-one';
    const RELATION_MANY_TO_MANY = 'many-to-many';
    const RELATION_MANY_TO_ONE  = 'many-to-one';
    const RELATION_ONE_TO_MANY  = 'one-to-many';

    const SEARCH_ENTITY_PERMISSION = 'VIEW';

    /** @var EngineInterface */
    protected $engine;

    /** @var ObjectManager */
    protected $em;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var SecurityProvider */
    protected $securityProvider;

    /** @var AclHelper */
    protected $searchAclHelper;

    /** @var bool */
    protected $isAllowedApplyAcl = true;

    /** @var string */
    protected $searchHandlerState;

    /**
     * @param ObjectManager       $em
     * @param EngineInterface     $engine
     * @param ObjectMapper        $mapper
     * @param SecurityProvider    $securityProvider
     * @param AclHelper           $searchAclHelper
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ObjectManager            $em,
        EngineInterface          $engine,
        ObjectMapper             $mapper,
        SecurityProvider         $securityProvider,
        AclHelper                $searchAclHelper,
        EventDispatcherInterface $dispatcher
    ) {
        $this->em               = $em;
        $this->engine           = $engine;
        $this->mapper           = $mapper;
        $this->securityProvider = $securityProvider;
        $this->searchAclHelper  = $searchAclHelper;
        $this->dispatcher       = $dispatcher;
    }

    /**
     * Get array with mapped entities
     *
     * @return array
     */
    public function getEntitiesListAliases()
    {
        return $this->mapper->getEntitiesListAliases();
    }

    /**
     * Gets search aliases for entities
     *
     * @param string[] $classNames The list of entity FQCN
     *
     * @return array [entity class name => entity search alias, ...]
     *
     * @throws \InvalidArgumentException if some of requested entities is not registered in the search index
     *                                   or has no the search alias
     */
    public function getEntityAliases(array $classNames = [])
    {
        return $this->mapper->getEntityAliases($classNames);
    }

    /**
     * Gets the search alias of a given entity
     *
     * @param string $className The FQCN of an entity
     *
     * @return string|null The search alias of the entity
     *                     or NULL if the entity is not registered in a search index or has no the search alias
     */
    public function getEntityAlias($className)
    {
        return $this->mapper->getEntityAlias($className);
    }

    /**
     * Get list of entities allowed to user
     *
     * @return array
     */
    public function getAllowedEntitiesListAliases()
    {
        return $this->filterAllowedEntities(self::SEARCH_ENTITY_PERMISSION, $this->getEntitiesListAliases());
    }

    /**
     * @param  string  $searchString
     * @param  integer $offset
     * @param  integer $maxResults
     * @param  string  $from
     * @param  integer $page
     *
     * @return Query
     */
    public function getSimpleSearchQuery($searchString, $offset = 0, $maxResults = 0, $from = null, $page = 0)
    {
        $searchString = trim($searchString);
        $query        = $this->select();

        if ($from) {
            $query->from($from);
        } else {
            $query->from('*');
        }

        if ($searchString) {
            $query->andWhere(self::TEXT_ALL_DATA_FIELD, Query::OPERATOR_CONTAINS, $searchString, Query::TYPE_TEXT);
        }

        if ($maxResults > 0) {
            $query->setMaxResults($maxResults);
        } else {
            $query->setMaxResults(Query::INFINITY);
        }

        if ($page > 0) {
            $query->setFirstResult($maxResults * ($page - 1));
        } elseif ($offset > 0) {
            $query->setFirstResult($offset);
        }

        return $query;
    }

    /**
     * @param  string  $searchString
     * @param  integer $offset
     * @param  integer $maxResults
     * @param  string  $from
     * @param  integer $page
     * @return Result
     */
    public function simpleSearch($searchString, $offset = 0, $maxResults = 0, $from = null, $page = 0)
    {
        $query = $this->getSimpleSearchQuery($searchString, $offset, $maxResults, $from, $page);

        return $this->query($query);
    }

    /**
     * Get query builder with select instance
     *
     * @return Query
     */
    public function select()
    {
        $query = new Query(Query::SELECT);

        $query->setMappingConfig($this->mapper->getMappingConfig());
        $query->setEntityManager($this->em);

        return $query;
    }

    /**
     * Run query with query builder
     *
     * @param  Query $query
     * @return Result
     */
    public function query(Query $query)
    {
        $this->prepareQuery($query);
        // we haven't allowed entities, so return null search result
        if (count($query->getFrom()) == 0) {
            return new Result($query, [], 0);
        }

        return $this->engine->search($query);
    }

    /**
     * Advanced search from API
     *
     * @param  string $expression
     * @return Result
     */
    public function advancedSearch($expression)
    {
        $lexer  = new Lexer();
        $parser = new ExpressionParser();

        /** @var Query $query */
        $query = $parser->parse($lexer->tokenize($expression));

        $query->setMappingConfig($this->mapper->getMappingConfig());

        /** @var Result $result */
        $result = $this->query($query);

        return $result;
    }

    /**
     * @param bool $value
     */
    public function setIsAllowedApplyAcl($value)
    {
        $this->isAllowedApplyAcl = (bool)$value;
    }

    /**
     * @param string $value
     */
    public function setSearchHandlerState($value)
    {
        $this->searchHandlerState = $value;
    }

    /**
     * Do query manipulations such as ACL apply etc.
     *
     * @param Query $query
     */
    protected function prepareQuery(Query $query)
    {
        $this->applyModesBehavior($query);

//        $event = new IndexerPrepareQueryEvent($query, $this->searchHandlerState);
//        $this->dispatcher->dispatch(IndexerPrepareQueryEvent::EVENT_NAME, $event);
//        $query = $event->getQuery();

        if ($this->isAllowedApplyAcl) {
            $this->searchAclHelper->apply($query);
        }
    }

    /**
     * Apply special behavior of class inheritance processing
     *
     * @param Query $query
     */
    protected function applyModesBehavior(Query $query)
    {
        // process abstract indexes
        // make hashes increasing performance
        $fromParts   = (array) $query->getFrom();
        $fromHash    = array_combine($fromParts, $fromParts);
        $aliases     = $this->mapper->getEntitiesListAliases();
        $aliasesHash = array_flip($aliases);

        if (!isset($fromHash['*'])) {
            foreach ($fromParts as $part) {
                $entityName = $part;
                $isAlias    = false;
                if (isset($aliasesHash[$part])) {
                    // find real name by alias
                    $entityName = $aliasesHash[$part];
                    $isAlias    = true;
                }

                $mode        = $this->mapper->getEntityModeConfig($entityName);
                $descendants = $this->mapper->getRegisteredDescendants($entityName);
                if (false !== $descendants) {
                    // add descendants to from clause
                    foreach ($descendants as $fromPart) {
                        if ($isAlias) {
                            $fromPart = $aliases[$fromPart];
                        }
                        if (!isset($fromHash[$fromPart])) {
                            $fromHash[$fromPart] = $fromPart;
                        }
                    }
                }

                if ($mode === Mode::ONLY_DESCENDANTS) {
                    unset($fromHash[$part]);
                }
            }
        }

        $collectedParts = array_values($fromHash);
        if ($collectedParts !== $fromParts) {
            $query->from($collectedParts);
        }
    }

    /**
     * Filter array of entities. Return array of allowed entities
     *
     * @param  string   $attribute Permission
     * @param  string[] $entities  The list of entity class names to be checked
     * @return string[]
     */
    protected function filterAllowedEntities($attribute, $entities)
    {
        foreach (array_keys($entities) as $entityClass) {
            $objectString = 'Entity:' . $entityClass;

            if ($this->securityProvider->isProtectedEntity($entityClass)
                && !$this->securityProvider->isGranted($attribute, $objectString)
            ) {
                unset($entities[$entityClass]);
            }
        }

        return $entities;
    }
}
