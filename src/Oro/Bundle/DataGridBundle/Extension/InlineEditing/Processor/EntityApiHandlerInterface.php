<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor;

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
