<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Oro\Bundle\SoapBundle\Model\RelationIdentifier;
use Oro\Component\PhpUtils\ArrayUtil;

class ActivityEntityDeleteHandlerRegistry
{
    /**
     * @var array
     */
    protected $activityEntityDeleteHandler;

    /**
     * @var bool
     */
    protected $isSorted = false;

    /**
     * @param RelationIdentifier $relationIdentifier
     *
     * @return ActivityEntityDeleteHandlerInterface
     */
    public function getHandler(RelationIdentifier $relationIdentifier)
    {
        foreach ($this->getOrderedHandlers() as $handlerConfiguration) {
            /** @var ActivityEntityDeleteHandlerInterface $handler */
            $handler = $handlerConfiguration['handler'];
            if ($handler->isApplicable($relationIdentifier)) {
                return $handler;
            }
        }

        throw new \RuntimeException('Applicable Delete Handler is not registered');
    }

    /**
     * @return ActivityEntityDeleteHandlerInterface[]
     */
    protected function getOrderedHandlers()
    {
        if (!$this->isSorted) {
            ArrayUtil::sortBy($this->activityEntityDeleteHandler);
            $this->isSorted = true;
        }

        return $this->activityEntityDeleteHandler;
    }

    /**
     * @param ActivityEntityDeleteHandlerInterface $handler
     * @param integer                $priority
     */
    public function addActivityEntityDeleteHandler(ActivityEntityDeleteHandlerInterface $handler, $priority = 0)
    {
        $this->isSorted = false;
        $this->activityEntityDeleteHandler[] = ['handler' => $handler, 'priority' => $priority];
    }
}
