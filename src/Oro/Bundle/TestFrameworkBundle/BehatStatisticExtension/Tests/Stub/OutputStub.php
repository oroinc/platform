<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub;

use Symfony\Component\Console\Output\Output;

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

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
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
