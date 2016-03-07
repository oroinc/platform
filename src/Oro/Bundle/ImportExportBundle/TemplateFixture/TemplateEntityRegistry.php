<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

use ArrayIterator;
use Iterator;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

class TemplateEntityRegistry
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var bool */
    private $isDirty = true;

    /** @var array */
    private $entities = [];

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $entityClass
     * @param string $entityKey
     * @param mixed  $entity
     *
     * @throws \LogicException
     */
    public function addEntity($entityClass, $entityKey, $entity)
    {
        if (!isset($this->entities[$entityClass])) {
            $this->entities[$entityClass] = [];
        }
        if (isset($this->entities[$entityClass][$entityKey])) {
            throw new LogicException(
                sprintf(
                    'The entity "%s" with key "%s" is already exist.',
                    $entityClass,
                    $entityKey
                )
            );
        }

        $this->entities[$entityClass][$entityKey] = ['entity' => $entity, 'isDirty' => true];

        $this->isDirty = true;
    }

    /**
     * @param string $entityClass
     * @param string $entityKey
     *
     * @return bool
     */
    public function hasEntity($entityClass, $entityKey)
    {
        return isset($this->entities[$entityClass][$entityKey]);
    }

    /**
     * @param string $entityClass
     * @param string $entityKey
     *
     * @return mixed
     *
     * @throws LogicException
     */
    public function getEntity($entityClass, $entityKey)
    {
        if (!isset($this->entities[$entityClass][$entityKey])) {
            throw new LogicException(
                sprintf(
                    'The entity "%s" with key "%s" does not exist.',
                    $entityClass,
                    $entityKey
                )
            );
        }

        return $this->entities[$entityClass][$entityKey]['entity'];
    }

    /**
     * @param TemplateManager $templateManager
     * @param string          $entityClass
     * @param string|null     $entityKey
     *
     * @return Iterator
     */
    public function getData(TemplateManager $templateManager, $entityClass, $entityKey = null)
    {
        $this->ensureReadyToWork($templateManager);

        $entities = [];
        if (null !== $entityKey) {
            $entities[] = $this->getEntity($entityClass, $entityKey);
        } elseif (isset($this->entities[$entityClass])) {
            foreach ($this->entities[$entityClass] as $val) {
                $entities[] = $val['entity'];
            }
        }

        return new ArrayIterator($entities);
    }

    /**
     * Makes sure all entities in this registry are ready to work.
     * It means that all properties of these entities are filled.
     *
     * @param TemplateManager $templateManager
     */
    protected function ensureReadyToWork(TemplateManager $templateManager)
    {
        while ($this->isDirty) {
            $this->isDirty = false;
            foreach ($this->entities as $className => &$entities) {
                foreach ($entities as $entityKey => &$val) {
                    if ($val['isDirty']) {
                        $repository = $templateManager->getEntityRepository($className);
                        $repository->fillEntityData($entityKey, $val['entity']);
                        $val['isDirty'] = false;
                    }
                }
            }

            if ($this->dispatcher && $this->dispatcher->hasListeners(Events::AFTER_LOAD_TEMPLATE_FIXTURES)) {
                $event = new LoadTemplateFixturesEvent($this->entities);
                $this->dispatcher->dispatch(Events::AFTER_LOAD_TEMPLATE_FIXTURES, $event);
                $this->entities = $event->getEntities();
            }
        }
    }
}
