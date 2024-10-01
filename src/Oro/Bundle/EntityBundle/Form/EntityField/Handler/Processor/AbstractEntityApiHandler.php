<?php

namespace Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor;

abstract class AbstractEntityApiHandler implements EntityApiHandlerInterface
{
    #[\Override]
    public function preProcess($entity)
    {
    }

    #[\Override]
    public function beforeProcess($entity)
    {
    }

    #[\Override]
    public function afterProcess($entity)
    {
    }

    #[\Override]
    public function invalidateProcess($entity)
    {
    }
}
