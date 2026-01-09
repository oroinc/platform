<?php

namespace Oro\Component\Testing\Unit\Command\Stub;

use Symfony\Component\Console\Output\Output;

/**
 * Captures console output messages for testing purposes.
 */
class OutputStub extends Output
{
    /**
     * @var array
     */
    public $messages = [];

    /**
     * @var string
     */
    private $output = '';

    #[\Override]
    protected function doWrite($message, $newline): void
    {
        $this->messages[] = $message;
        $this->output .= $message;

        if ($newline) {
            $this->output .= "\n";
        }
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }
}
