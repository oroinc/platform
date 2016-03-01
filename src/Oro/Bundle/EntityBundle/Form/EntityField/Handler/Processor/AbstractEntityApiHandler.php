<?php

namespace Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor;

abstract class AbstractEntityApiHandler implements EntityApiHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function preProcess($entity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function beforeProcess($entity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcess($entity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateProcess($entity)
    {
    }
}
