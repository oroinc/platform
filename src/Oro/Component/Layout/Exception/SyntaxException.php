<?php

namespace Oro\Component\Layout\Exception;

/**
 * Error in layout definition
 */
class SyntaxException extends LogicException
{
    /** @var array */
    protected $source;

    /**
     * @param string $message
     * @param array|mixed $source on incorrect source type expected non-array values
     * @param string $path
     * @param \Throwable|null $previous
     */
    public function __construct(string $message, $source, string $path = '.', \Throwable $previous = null)
    {
        $this->source = $source;

        parent::__construct(sprintf('Syntax error: %s at "%s"', $message, $path), 0, $previous);
    }

    /**
     * @return array|mixed
     */
    public function getSource()
    {
        return $this->source;
    }
}
