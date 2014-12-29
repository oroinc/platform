<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

interface TemplateEntityRepositoryInterface
{
    /**
     * Gets the class name of the entity this fixture is worked with
     */
    public function getEntityClass();

    /**
     * Gets entity by its key.
     * If the entity with the given key does not exist it will be created and registered in the repository.
     *
     * @param string|null $key The entity key.
     *
     * @return mixed
     */
    public function getEntity($key);

    /**
     * Sets data to all fields including relations of the given entity.
     *
     * @param string $key    The entity key.
     * @param object $entity The entity.
     *
     * @throws \LogicException
     */
    public function fillEntityData($key, $entity);
}
