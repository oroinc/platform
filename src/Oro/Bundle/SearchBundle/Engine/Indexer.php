<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Parser;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Security\SecurityProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\User;

class Indexer
{
    const TEXT_ALL_DATA_FIELD   = 'all_text';

    const RELATION_ONE_TO_ONE   = 'one-to-one';
    const RELATION_MANY_TO_MANY = 'many-to-many';
    const RELATION_MANY_TO_ONE  = 'many-to-one';
    const RELATION_ONE_TO_MANY  = 'one-to-many';

    const SEARCH_ENTITY_PERMISSION = 'VIEW';

    /**
     * @var EngineInterface
     */
    protected $engine;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var ObjectMapper
     */
    protected $mapper;

    /**
     * @var SecurityProvider
     */
    protected $securityProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var EntityProvider
     */
    protected $entityProvider;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param ObjectManager     $em
     * @param EngineInterface   $engine
     * @param ObjectMapper      $mapper
     * @param SecurityProvider  $securityProvider
     * @param ConfigManager     $configManager
     * @param EntityProvider    $entityProvider
     * @param Translator        $translator
     */
    public function __construct(
        ObjectManager       $em,
        EngineInterface     $engine,
        ObjectMapper        $mapper,
        SecurityProvider    $securityProvider,
        ConfigManager       $configManager,
        EntityProvider      $entityProvider,
        Translator          $translator
    ) {
        $this->em               = $em;
        $this->engine           = $engine;
        $this->mapper           = $mapper;
        $this->securityProvider = $securityProvider;
        $this->configManager    = $configManager;
        $this->entityProvider   = $entityProvider;
        $this->translator       = $translator;
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
     * @return Result
     */
    public function simpleSearch($searchString, $offset = 0, $maxResults = 0, $from = null, $page = 0)
    {
        $searchString = trim($searchString);
        $query = $this->select();

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

        return $this->query($query);
    }

    /**
     * @param User $user
     * @param string $searchString
     * @param int $offset
     * @param int $maxResults
     * @return array
     */
    public function autocompleteSearch(User $user, $searchString, $offset = 0, $maxResults = 0)
    {
        $entityDescriptions = $this->entityProvider->getEntities();
        $classNames = [];
        foreach ($entityDescriptions as $description) {
            $classNames[] = $description['name'];
        }
        $entities = $this->em->getRepository(EntityConfigModel::ENTITY_NAME)->findEntitiesByClassNames($classNames);

        $tables = [];
        foreach ($entities as $entity) {
            /** @var EntityConfigModel $entity */
            $data = $entity->toArray('activity');
            if (!isset($data['activities']) || !$data['activities']) {
                continue;
            }

            if (!in_array(Email::ENTITY_CLASS, $data['activities'])) {
                continue;
            }

            $metadata = $this->em->getClassMetadata($entity->getClassName());
            $tables[] = $metadata->getTableName();
        }

        $results = [];
        $searchResults = $this->simpleSearch($searchString, $offset, $maxResults, $tables);
        foreach ($searchResults->getElements() as $item) {
            $className = $item->getEntityName();
            if (ClassUtils::getClass($user) === $className && $user->getId() === $item->getRecordId()) {
                continue;
            }
            $text = $item->getRecordTitle();
            if ($label = $this->getClassLabel($className)) {
                $text .= ' (' . $label . ')';
            }
            $results[] = [
                'text' => $text,
                'id'   => json_encode([
                    'entityClass' => $className,
                    'entityId'    => $item->getRecordId(),
                ]),
            ];
        }

        return $results;
    }

    /**
     * @param User $user
     * @param string $searchString
     * @return array
     */
    public function autocompleteSearchById(User $user, $searchString)
    {
        $results = [];
        if ($searchString) {
            $targets = explode(';', $searchString);
            foreach ($targets as $target) {
                if (!$target) {
                    continue;
                }
                $target = json_decode($target, true);
                if (!isset($target['entityClass']) || !$target['entityClass']
                    || !isset($target['entityId']) || !$target['entityId']) {
                    continue;
                }
                if (ClassUtils::getClass($user) === $target['entityClass'] && $user->getId() === $target['entityId']) {
                    continue;
                }
                $entity = $this->em->getRepository($target['entityClass'])->find($target['entityId']);
                if ($fields = $this->mapper->getEntityMapParameter($target['entityClass'], 'title_fields')) {
                    $text = [];
                    foreach ($fields as $field) {
                        $text[] = $this->mapper->getFieldValue($entity, $field);
                    }
                } else {
                    $text = [(string) $entity];
                }
                $text = implode(' ', $text);
                if ($label = $this->getClassLabel($target['entityClass'])) {
                    $text .= ' (' . $label . ')';
                }
                $results[] = [
                    'text' => $text,
                    'id'   => json_encode([
                        'entityClass' => $target['entityClass'],
                        'entityId'    => $target['entityId'],
                    ]),
                ];
            }
        }

        return $results;
    }

    /**
     * @param string $className
     * @return null|string
     */
    protected function getClassLabel($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }
        $entityConfig = new EntityConfigId('entity', $className);
        $label = $this->configManager->getConfig($entityConfig)->get('label');

        return $this->translator->trans($label);
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
     * @param  Query  $query
     * @return Result
     */
    public function query(Query $query)
    {
        $this->prepareQuery($query);
        // we haven't allowed entities, so return null search result
        if (count($query->getFrom()) == 0) {
            return new Result($query, array(), 0);
        }

        return $this->engine->search($query);
    }

    /**
     * Advanced search from API
     *
     * @param  string $searchString
     * @return Result
     */
    public function advancedSearch($searchString)
    {
        $parser = new Parser($this->mapper->getMappingConfig());

        return $this->query($parser->getQueryFromString($searchString));
    }

    /**
     * Do query manipulations such as ACL apply etc.
     *
     * @param Query $query
     */
    protected function prepareQuery(Query $query)
    {
        $this->applyModesBehavior($query);
        $this->applyAclToQuery($query);
    }

    /**
     * Apply ACL to search Query.
     * Removes all entities of the request to which the user has no access
     *
     * @param Query $query
     */
    protected function applyAclToQuery(Query $query)
    {
        $allowedEntities = $this->getAllowedEntitiesListAliases();
        $queryFromEntities = $query->getFrom();
        $entitiesList = array_values($allowedEntities);

        // in query, from record !== '*'
        if (!empty($queryFromEntities) && $queryFromEntities[0] !== '*') {
            foreach ($queryFromEntities as $key => $fromEntityAlias) {
                if (!in_array($fromEntityAlias, $entitiesList)) {
                    unset($queryFromEntities[$key]);
                }
            }
            $query->from($queryFromEntities);
        } elseif ($allowedEntities != $this->mapper->getEntitiesListAliases()) {
            $query->from($allowedEntities);
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
        $fromParts   = (array)$query->getFrom();
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
                unset ($entities[$entityClass]);
            }
        }

        return $entities;
    }
}
