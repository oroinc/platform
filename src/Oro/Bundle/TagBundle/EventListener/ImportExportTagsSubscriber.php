<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Countable;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\ImportExportBundle\Event\ReadEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\TagBundle\Manager\TagImportManager;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;

class ImportExportTagsSubscriber implements EventSubscriberInterface
{
    /** @var ServiceLink */
    protected $tagManagerLink;

    /** @var Taggable[] */
    protected $pendingTaggedObjects = [];

    /** @var Taggable[] */
    protected $preparedTaggedObjects = [];

    /** @var object */
    protected $importedEntity;

    /**
     * @param ServiceLink $tagManagerLink
     */
    public function __construct(ServiceLink $tagManagerLink)
    {
        $this->tagManagerLink = $tagManagerLink;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::READ_ENTITY => 'readEntity',
            Events::NORMALIZE_ENTITY => 'normalizeEntity',
            Events::DENORMALIZE_ENTITY => 'denormalizeEntity',
            Events::LOAD_ENTITY_RULES_AND_BACKEND_HEADERS => 'loadEntityRulesAndBackendHeaders',
            Events::LOAD_TEMPLATE_FIXTURES => 'addTagsIntoFixtures',
            StrategyEvent::PROCESS_BEFORE => ['beforeImport', 255],
            StrategyEvent::PROCESS_AFTER => ['afterImport', -255],
        ];
    }

    /**
     * Stores currently imported entity for later use
     *
     * @param StrategyEvent $event
     */
    public function beforeImport(StrategyEvent $event)
    {
        $this->importedEntity = $event->getEntity();
    }

    /**
     * Checks if the entity is the same object as before import
     * (in case it is not - update tags)
     *
     * @param StrategyEvent $event
     */
    public function afterImport(StrategyEvent $event)
    {
        if (!$this->pendingTaggedObjects ||
            $event->getEntity() === $this->importedEntity ||
            !$event->getEntity() instanceof Taggable
        ) {
            $this->importedEntity = null;

            return;
        }

        $event->getEntity()->setTags($this->importedEntity->getTags());
        unset($this->pendingTaggedObjects[spl_object_hash($this->importedEntity)]);
        $this->pendingTaggedObjects[spl_object_hash($event->getEntity())] = $event->getEntity();

        $this->importedEntity = null;
    }

    /**
     * @param ReadEntityEvent $event
     */
    public function readEntity(ReadEntityEvent $event)
    {
        $entity = $event->getObject();
        if ($entity instanceof Taggable) {
            $this->getTagImportManager()->loadTags($entity);
        }
    }

    /**
     * @param DenormalizeEntityEvent $event
     */
    public function denormalizeEntity(DenormalizeEntityEvent $event)
    {
        $object = $event->getObject();
        $data = $event->getData();
        if (!$object instanceof Taggable) {
            return;
        }

        $tags = $this->getTagImportManager()->denormalizeTags($data);
        if ($tags) {
            $object->setTags($tags);
            $this->pendingTaggedObjects[spl_object_hash($object)] = $object;
        }
    }

    /**
     * Prepare taggable objects to save their tags
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->pendingTaggedObjects) {
            return;
        }

        $uow = $args->getEntityManager()->getUnitOfWork();
        $flushedEntities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        $this->preparedTaggedObjects = array_merge(
            $this->preparedTaggedObjects,
            array_intersect_key($this->pendingTaggedObjects, $flushedEntities)
        );
        $this->pendingTaggedObjects = array_diff_key($this->pendingTaggedObjects, $this->preparedTaggedObjects);
    }

    /**
     * Saves tagging during import
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->preparedTaggedObjects) {
            return;
        }

        $taggables = $this->preparedTaggedObjects;
        $this->preparedTaggedObjects = [];
        array_map([$this->getTagImportManager(), 'saveTags'], $taggables);
    }

    /**
     * @param NormalizeEntityEvent $event
     */
    public function normalizeEntity(NormalizeEntityEvent $event)
    {
        if (!$event->isFullData() || !$event->getObject() instanceof Taggable) {
            return;
        }

        $event->setResultField(
            TagImportManager::TAGS_FIELD,
            $this->getTagImportManager()->normalizeTags($event->getObject()->getTags())
        );
    }

    /**
     * @param LoadEntityRulesAndBackendHeadersEvent $event
     */
    public function loadEntityRulesAndBackendHeaders(LoadEntityRulesAndBackendHeadersEvent $event)
    {
        if (!$event->isFullData() ||
            !in_array('Oro\Bundle\TagBundle\Entity\Taggable', class_implements($event->getEntityName()))
        ) {
            return;
        }

        array_map(
            [$event, 'addHeader'],
            $this->getTagImportManager()->createTagHeaders(
                $event->getEntityName(),
                $event->getConvertDelimiter(),
                $event->getConversionType()
            )
        );

        list($name, $rule) = $this->getTagImportManager()->createTagRule($event->getConvertDelimiter());
        $event->setRule($name, $rule);
    }

    /**
     * @param LoadTemplateFixturesEvent $event
     */
    public function addTagsIntoFixtures(LoadTemplateFixturesEvent $event)
    {
        foreach ($event->getEntities() as $entityRecords) {
            foreach ($entityRecords as $record) {
                $entity = $record['entity'];
                if (!$entity instanceof Taggable ||
                    (($entity->getTags() instanceof Countable || is_array($entity->getTags())) &&
                        count($entity->getTags()))
                ) {
                    continue;
                }

                $entity
                    ->setTags([
                        'autocomplete' => [],
                        'all' => [
                            new Tag('custom tag'),
                            new Tag('second tag'),
                        ],
                        'owner' => [],
                    ]);
            }
        }
    }

    /**
     * @return TagImportManager
     */
    protected function getTagImportManager()
    {
        return $this->tagManagerLink->getService();
    }
}
