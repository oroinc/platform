<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Countable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\ImportExportBundle\Event\AfterEntityPageLoadedEvent;
use Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\TagBundle\Manager\TagImportManager;
use Oro\Bundle\TagBundle\Entity\Tag;

class ImportExportTagsSubscriber implements EventSubscriberInterface
{
    /** @var ServiceLink */
    protected $tagManagerLink;

    /** @var object[] */
    protected $pendingTaggedObjects = [];

    /** @var object[] */
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
            Events::AFTER_ENTITY_PAGE_LOADED => 'updateEntityResults',
            Events::AFTER_NORMALIZE_ENTITY => 'normalizeEntity',
            Events::AFTER_DENORMALIZE_ENTITY => 'denormalizeEntity',
            Events::AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS => 'loadEntityRulesAndBackendHeaders',
            Events::AFTER_LOAD_TEMPLATE_FIXTURES => 'addTagsIntoFixtures',
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
            !$this->getTagImportManager()->isTaggable($event->getEntity())
        ) {
            $this->importedEntity = null;

            return;
        }

        $this->getTagImportManager()->moveTags($this->importedEntity, $event->getEntity());
        unset($this->pendingTaggedObjects[spl_object_hash($this->importedEntity)]);
        $this->pendingTaggedObjects[spl_object_hash($event->getEntity())] = $event->getEntity();

        $this->importedEntity = null;
    }

    /**
     * @param AfterEntityPageLoadedEvent $event
     */
    public function updateEntityResults(AfterEntityPageLoadedEvent $event)
    {
        $this->getTagImportManager()->loadTags($event->getRows());
    }

    /**
     * @param DenormalizeEntityEvent $event
     */
    public function denormalizeEntity(DenormalizeEntityEvent $event)
    {
        $object = $event->getObject();
        $data = $event->getData();
        if (!$this->getTagImportManager()->isTaggable($object)) {
            return;
        }

        $tags = $this->getTagImportManager()->denormalizeTags($data);
        if ($tags) {
            $this->getTagImportManager()->setTags($object, $tags);
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
        $this->getTagImportManager()->clearImportedTags();
        if (!$this->pendingTaggedObjects) {
            return;
        }

        $uow = $args->getEntityManager()->getUnitOfWork();
        // adds managed objects with updated tags into "preparedTaggedObjects" for further processing
        $this->preparedTaggedObjects = array_merge(
            $this->preparedTaggedObjects,
            array_filter(
                $this->pendingTaggedObjects,
                function ($object) use ($uow) {
                    return $uow->isEntityScheduled($object) || $uow->isInIdentityMap($object);
                }
            )
        );
        /*
         * Removes managed objects with updated tags from "pendingTaggedObjects"
         * as they progressed further in previous command
         */
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
        if (!$taggables) {
            return;
        }

        $this->preparedTaggedObjects = [];
        // persist tags of all $taggables objects
        array_walk($taggables, [$this->getTagImportManager(), 'persistTags']);
        $args->getEntityManager()->flush();
    }

    /**
     * @param NormalizeEntityEvent $event
     */
    public function normalizeEntity(NormalizeEntityEvent $event)
    {
        if (!$event->isFullData() || !$this->getTagImportManager()->isTaggable($event->getObject())) {
            return;
        }

        $event->setResultField(
            TagImportManager::TAGS_FIELD,
            $this->getTagImportManager()->normalizeTags($this->getTagImportManager()->getTags($event->getObject()))
        );
    }

    /**
     * @param LoadEntityRulesAndBackendHeadersEvent $event
     */
    public function loadEntityRulesAndBackendHeaders(LoadEntityRulesAndBackendHeadersEvent $event)
    {
        if (!$event->isFullData() ||
            !$this->getTagImportManager()->isTaggable($event->getEntityName())
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
                if (!$this->getTagImportManager()->isTaggable($entity)) {
                    continue;
                }

                $tags = $this->getTagImportManager()->getTags($entity);
                if (($tags instanceof Countable || is_array($tags)) && count($tags)) {
                    continue;
                }

                $this->getTagImportManager()->setTags(
                    $entity,
                    new ArrayCollection([
                        new Tag('custom tag'),
                        new Tag('second tag'),
                    ])
                );
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
