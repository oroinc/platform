<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\Handler\Extra;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor\EntityApiHandlerInterface;

/**
 * Test handler. Remove it in future.
 *
 * Class ContactApiHandler
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\Handler\Extra
 */
class ContactApiHandler implements EntityApiHandlerInterface
{
    const HANDLER_KEY = 'oro.entity.api.extra.contact';
    const ENTITY_CLASS = 'OroCRM\Bundle\ContactBundle\Entity\Contact';

    /**
     * {@inheritdoc}
     */
    public function preProcess($entity)
    {
        // TODO: Implement preProcess() method.
    }

    /**
     * {@inheritdoc}
     */
    public function beforeProcess($entity)
    {
        // TODO: Implement process() method.
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcess($entity)
    {
        // TODO: Implement afterProcess() method.
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateProcess($entity)
    {
        // TODO: Implement invalidateProcess() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return self::ENTITY_CLASS;
    }
}
