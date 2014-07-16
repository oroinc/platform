<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;

abstract class AbstractTemplateRepository implements
    TemplateEntityRepositoryInterface,
    TemplateManagerAwareInterface
{
    /** @var TemplateManager */
    protected $templateManager;

    /**
     * {@inheritdoc}
     */
    public function setTemplateManager(TemplateManager $templateManager)
    {
        $this->templateManager = $templateManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($key)
    {
        $this->ensureEntityRegistered($key);

        return $this->templateManager->getEntityRegistry()
            ->getEntity($this->getEntityClass(), $key);
    }

    /**
     * {@inheritdoc}
     */
    public function fillEntityData($key, $entity)
    {
        // just throw an exception to indicate that derived class cannot fill data to the given entity
        throw new LogicException(
            sprintf(
                'Unknown entity: "%s"; key: "%s".',
                get_class($entity),
                $key
            )
        );
    }

    /**
     * Makes sure the entity with the given key is registered in the entity registry
     *
     * @param string $key
     */
    protected function ensureEntityRegistered($key)
    {
        if (!$this->templateManager->getEntityRegistry()->hasEntity($this->getEntityClass(), $key)) {
            $this->templateManager->getEntityRegistry()
                ->addEntity($this->getEntityClass(), $key, $this->createEntity($key));
        }
    }

    /**
     * @param string $key
     *
     * @return \Iterator
     */
    protected function getEntityData($key)
    {
        $this->ensureEntityRegistered($key);

        return $this->templateManager->getEntityRegistry()
            ->getData($this->templateManager, $this->getEntityClass(), $key);
    }

    /**
     * Creates a new instance of the entity
     *
     * @param string $key
     *
     * @return object
     */
    abstract protected function createEntity($key);
}
