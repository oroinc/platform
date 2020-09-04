<?php

namespace Oro\Bundle\UIBundle\Tools\HTMLPurifier;

/**
 * Holds info about an error inside the html content.
 */
class Error
{
    /** @var string */
    private $message;

    /** @var string */
    private $place;

    /**
     * @param string $message
     * @param string $place
     */
    public function __construct(string $message, string $place)
    {
        $this->message = $message;
        $this->place = $place;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getPlace(): string
    {
        return $this->place;
    }
}
