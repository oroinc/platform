<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs;

use Symfony\Component\Console\Output\Output;

class OutputStub extends Output
{
    /**
     * @var array
     */
    public $messages = [];

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
    {
        $this->messages[] = $message;
    }
}
