<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Expression\Lexer;
use Oro\Bundle\SearchBundle\Query\Expression\Parser as ExpressionParser;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Security\SecurityProvider;
use Oro\Bundle\SecurityBundle\Search\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Search index accessor class.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Indexer
{
    const TEXT_ALL_DATA_FIELD   = 'all_text';
    const NAME_FIELD            = 'system_entity_name';
    const ID_FIELD              = 'system_entity_id';

    const RELATION_ONE_TO_ONE   = 'one-to-one';
    const RELATION_MANY_TO_MANY = 'many-to-many';
    const RELATION_MANY_TO_ONE  = 'many-to-one';
    const RELATION_ONE_TO_MANY  = 'one-to-many';

    const SEARCH_ENTITY_PERMISSION = 'VIEW';

    /** @var ExtendedEngineInterface */
    protected $engine;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var SecurityProvider */
    protected $securityProvider;

    /** @var AclHelper */
    protected $searchAclHelper;

    /** @var bool */
    protected $isAllowedApplyAcl = true;

    public function __construct(
        ExtendedEngineInterface $engine,
        ObjectMapper $mapper,
        SecurityProvider $securityProvider,
        AclHelper $searchAclHelper
    ) {
        $this->engine = $engine;
        $this->mapper = $mapper;
        $this->securityProvider = $securityProvider;
        $this->searchAclHelper = $searchAclHelper;
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
     * @param string|null          $searchString
     * @param int|null             $offset
     * @param int|null             $maxResults
     * @param string|string[]|null $from
     * @param int|null             $page
     *
     * @return Query
     */
    public function getSimpleSearchQuery(
        ?string $searchString,
        ?int $offset = 0,
        ?int $maxResults = 0,
        $from = null,
        ?int $page = 0
    ): Query {
        $query = $this->select();
        $criteria = $query->getCriteria();

        $nameField = Criteria::implodeFieldTypeName(Query::TYPE_TEXT, self::NAME_FIELD);
        QueryBuilderUtil::checkField($nameField);

        $query->addSelect($nameField . ' as name');
        $query->from($from ?: '*');

        $searchString = trim($searchString);
        if ($searchString) {
            $criteria->where(Criteria::expr()->contains(
                Criteria::implodeFieldTypeName(Query::TYPE_TEXT, self::TEXT_ALL_DATA_FIELD),
                $searchString
            ));
        }

        $criteria->setMaxResults($maxResults > 0 ? $maxResults : Query::INFINITY);
        if ($page > 0) {
            $offset = $maxResults * ($page - 1);
        }
        if ($offset > 0) {
            $criteria->setFirstResult($offset);
        }

        return $query;
    }

    /**
     * @param string|null          $searchString
     * @param int|null             $offset
     * @param int|null             $maxResults
     * @param string|string[]|null $from
     * @param int|null             $page
     *
     * @return Result
     */
    public function simpleSearch(
        ?string $searchString,
        ?int $offset = 0,
        ?int $maxResults = 0,
        $from = null,
        ?int $page = 0
    ): Result {
        $query = $this->getSimpleSearchQuery($searchString, $offset, $maxResults, $from, $page);

        return $this->query($query);
    }

    /**
     * @param string|null $searchString
     * @param null        $from
     *
     * @return array
     * [
     *  <EntityFQCN> => <DocumentsCount>
     * ]
     */
    public function getDocumentsCountGroupByEntityFQCN(
        ?string $searchString,
        $from = null
    ): array {
        $query = $this->getSimpleSearchQuery($searchString, 0, 0, $from, 0);

        $this->prepareQuery($query);

        return $this->engine->getDocumentsCountGroupByEntityFQCN($query);
    }

    /**
     * Get query builder with select instance
     *
     * @return Query
     */
    public function select()
    {
        $query = new Query();

        $query->setMappingConfig($this->mapper->getMappingConfig());

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
        $from = $query->getFrom();
        if (is_array($from) && count($from) == 0) {
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
     * Do query manipulations such as ACL apply etc.
     */
    protected function prepareQuery(Query $query)
    {
        $this->applyModesBehavior($query);

        if ($this->isAllowedApplyAcl) {
            $this->searchAclHelper->apply($query);
        }
    }

    /**
     * Apply special behavior of class inheritance processing
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
