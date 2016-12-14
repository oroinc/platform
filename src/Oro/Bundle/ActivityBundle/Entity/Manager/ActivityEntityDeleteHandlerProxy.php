<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandlerInterface;

class ActivityEntityDeleteHandlerProxy implements DeleteHandlerInterface
{
    /**
     * @var ActivityEntityDeleteHandlerRegistry
     */
    protected $activityEntityDeleteHandlerRegistry;

    /**
     * @param ActivityEntityDeleteHandlerRegistry $activityEntityDeleteHandlerRegistry
     */
    public function __construct(ActivityEntityDeleteHandlerRegistry $activityEntityDeleteHandlerRegistry)
    {
        $this->activityEntityDeleteHandlerRegistry = $activityEntityDeleteHandlerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        return $this->activityEntityDeleteHandlerRegistry->getHandler($id)->handleDelete($id, $manager);
    }
}
