<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command\Stub;

use Symfony\Component\Console\Output\Output;

class TestOutput extends Output
{
    /**
     * @var array
     */
    public $messages = array();

    protected function doWrite($message, $newline)
    {
        $this->messages[] = $message;
    }
}
