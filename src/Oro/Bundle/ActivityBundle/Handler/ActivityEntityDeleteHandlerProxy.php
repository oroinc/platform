<?php

namespace Oro\Bundle\ActivityBundle\Handler;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;

/**
 * The handler that is used by the old REST API to delete an activity entity associations.
 */
class ActivityEntityDeleteHandlerProxy extends DeleteHandler
{
    /** @var ActivityEntityDeleteHandler */
    private $activityEntityDeleteHandler;

    public function __construct(ActivityEntityDeleteHandler $activityEntityDeleteHandler)
    {
        $this->activityEntityDeleteHandler = $activityEntityDeleteHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function handleDelete($id, ApiEntityManager $manager, array $options = []): void
    {
        /** @var RelationIdentifier $id */

        $this->activityEntityDeleteHandler->delete($id);
    }
}
