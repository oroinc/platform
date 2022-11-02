<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Loader\Generator;

use Oro\Component\PhpUtils\ClassGenerator;

/**
 * Class generation context provided to layout loader visitors.
 */
class VisitContext
{
    protected ClassGenerator $class;

    protected string $updateMethodBody = '';

    public function __construct(ClassGenerator $class)
    {
        $this->class  = $class;
    }

    public function getClass(): ClassGenerator
    {
        return $this->class;
    }

    /**
     * Updates the existing update method by appending the provided code and returns the updated update method body.
     */
    public function appendToUpdateMethodBody(string $code): string
    {
        $this->updateMethodBody .= "\n" . $code;

        return $this->updateMethodBody;
    }

    public function getUpdateMethodBody(): string
    {
        return $this->updateMethodBody;
    }

    public function setUpdateMethodBody(string $updateMethodBody): void
    {
        $this->updateMethodBody = $updateMethodBody;
    }
}
