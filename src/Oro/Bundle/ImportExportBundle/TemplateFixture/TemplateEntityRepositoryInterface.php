<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

/**
 * Defines the contract for template entity repositories.
 *
 * Implementations provide template fixture data for a specific entity class, including
 * methods to retrieve entities by key and to populate entity data with all necessary
 * field values and relationships. These repositories are used to generate export templates
 * and validate import data structure.
 */
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
