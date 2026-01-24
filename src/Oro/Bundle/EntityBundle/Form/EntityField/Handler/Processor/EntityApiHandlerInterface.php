<?php

namespace Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor;

/**
 * Defines the contract for entity API field handlers.
 *
 * Implementations of this interface handle the lifecycle of entity field updates
 * through the API, providing hooks for pre-processing, validation, post-processing,
 * and error handling. Each handler is responsible for a specific entity class.
 */
interface EntityApiHandlerInterface
{
    /**
     * Pre processing action. Run before data sets
     *
     * @param $entity
     * @return mixed
     */
    public function preProcess($entity);

    /**
     * Before processing action on valid data
     *
     * @param $entity
     * @return mixed
     */
    public function beforeProcess($entity);

    /**
     * Post processing action on success main process
     *
     * @param $entity
     * @return mixed
     */
    public function afterProcess($entity);

    /**
     * Post processing action if invalid data
     *
     * @param $entity
     * @return mixed
     */
    public function invalidateProcess($entity);

    /**
     * Target entity class
     *
     * @return string
     */
    public function getClass();
}
