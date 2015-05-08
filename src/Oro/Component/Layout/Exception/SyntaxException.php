<?php

namespace Oro\Component\Layout\Exception;

class SyntaxException extends LogicException
{
    /** @var string */
    protected $source;

    /**
     * @param string $message
     * @param string $source
     * @param string $path
     */
    public function __construct($message, $source, $path = '.')
    {
        $this->source = $source;

        parent::__construct(sprintf('Syntax error: %s at "%s"', $message, $path));
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }
}
