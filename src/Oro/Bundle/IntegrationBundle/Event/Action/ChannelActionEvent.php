<?php

namespace Oro\Bundle\IntegrationBundle\Event\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\EventDispatcher\Event;

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

    /**
     * @param Channel $channel
     */
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
