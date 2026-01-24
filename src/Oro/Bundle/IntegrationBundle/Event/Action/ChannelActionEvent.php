<?php

namespace Oro\Bundle\IntegrationBundle\Event\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Provides common functionality for integration channel action events.
 *
 * This base class encapsulates an integration channel and provides error collection functionality
 * for events related to channel operations. Subclasses should extend this to create specific
 * channel action events (e.g., enable, disable, synchronize).
 */
abstract class ChannelActionEvent extends Event
{
    const NAME = '';

    /**
     * @var Collection|string[]
     */
    private $errors;

    /**
     * @var Channel
     */
    private $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;

        $this->errors = new ArrayCollection();
    }

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $error
     */
    public function addError($error)
    {
        if (!$this->hasError($error)) {
            $this->errors->add($error);
        }
    }

    /**
     * @return Collection|string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param string $error
     *
     * @return bool
     */
    private function hasError($error)
    {
        return $this->errors->contains($error);
    }
}
