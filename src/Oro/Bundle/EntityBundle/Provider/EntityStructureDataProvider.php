<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides detailed information about entities
 * including options collected via "oro_entity.structure.options" event listeners.
 */
class EntityStructureDataProvider
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityWithFieldsProvider */
    private $entityWithFieldsProvider;

    /** @var EntityClassNameHelper */
    private $classNameHelper;

    /** @var TranslatorInterface */
    private $translator;

    /** @var Cache */
    private $cache;

    private const ENTITY_PROPERTY_MAPPINGS = [
        'name'         => 'setClassName',
        'label'        => 'setLabel',
        'plural_label' => 'setPluralLabel',
        'icon'         => 'setIcon',
        'routes'       => 'setRoutes'
    ];

    private const FIELD_PROPERTY_MAPPINGS = [
        'name'                => 'setName',
        'type'                => 'setType',
        'label'               => 'setLabel',
        'relation_type'       => 'setRelationType',
        'related_entity_name' => 'setRelatedEntityName'
    ];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityWithFieldsProvider $entityWithFieldsProvider,
        EntityClassNameHelper $classNameHelper,
        TranslatorInterface $translator,
        Cache $cache
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityWithFieldsProvider = $entityWithFieldsProvider;
        $this->classNameHelper = $classNameHelper;
        $this->translator = $translator;
        $this->cache = $cache;
    }

    /**
     * @return EntityStructure[]
     */
    public function getEntities()
    {
        $cacheKey = $this->getCacheKey();
        $entityStructures = $this->cache->fetch($cacheKey);
        if (false === $entityStructures) {
            $entityStructures = $this->processEntities();

            $event = new EntityStructureOptionsEvent();
            $event->setData($entityStructures);
            $this->eventDispatcher->dispatch($event, EntityStructureOptionsEvent::EVENT_NAME);
            $entityStructures = $event->getData();

            $this->cache->save($cacheKey, $entityStructures);
        }

        return $entityStructures;
    }

    /**
     * @param string $entityName The class name or url-safe class name of the entity
     *
     * @return EntityStructure
     */
    public function getEntity($entityName)
    {
        $entityClass = $this->classNameHelper->resolveEntityClass($entityName);
        $entity = $this->entityWithFieldsProvider
            ->getFieldsForEntity($entityClass, true, true, true, false, true, true);
        $model = $this->processEntity($entity);

        $event = new EntityStructureOptionsEvent();
        $event->setData([$model]);
        $this->eventDispatcher->dispatch($event, EntityStructureOptionsEvent::EVENT_NAME);

        return $model;
    }

    /**
     * @return EntityStructure[]
     */
    private function processEntities()
    {
        $result = [];

        $data = $this->entityWithFieldsProvider->getFields(true, true, true, false, true, true);
        foreach ($data as $item) {
            $result[] = $this->processEntity($item);
        }

        return $result;
    }

    /**
     * @param array $entity
     *
     * @return EntityStructure
     */
    private function processEntity(array $entity)
    {
        $model = new EntityStructure();
        foreach (self::ENTITY_PROPERTY_MAPPINGS as $name => $method) {
            if (isset($entity[$name])) {
                $model->{$method}($entity[$name]);
            }
        }

        $entityClass = $model->getClassName();
        $model->setId($this->classNameHelper->getUrlSafeClassName($entityClass));
        if (!empty($entity['fields'])) {
            $this->processFields($model, $entity['fields']);
        }

        return $model;
    }

    private function processFields(EntityStructure $structure, array $fields)
    {
        foreach ($fields as $field) {
            $model = new EntityFieldStructure();
            foreach (self::FIELD_PROPERTY_MAPPINGS as $name => $method) {
                if (isset($field[$name])) {
                    $model->{$method}($field[$name]);
                }
            }
            $structure->addField($model);
        }
    }

    protected function getCacheKey(): string
    {
        return sprintf('data.%s', $this->translator->getLocale());
    }
}
