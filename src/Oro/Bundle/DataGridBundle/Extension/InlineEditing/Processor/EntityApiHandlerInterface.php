<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor;

interface EntityApiHandlerInterface
{
    /**
     * Pre processing action
     *
     * @param $entity
     * @return mixed
     */
    public function preProcess($entity);

    /**
     * On processing action
     *
     * @param $entity
     * @return mixed
     */
    public function onProcess($entity);

    /**
     * Post processing action
     *
     * @param $entity
     * @return mixed
     */
    public function afterProcess($entity);

    /**
     * Target entity class
     *
     * @return string
     */
    public function getClass();
}
