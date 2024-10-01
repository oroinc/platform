<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Output\Printer;

use Behat\Testwork\Output\Printer\OutputPrinter;

/**
 * Fake OutputPrinter
 * Unable to create DbOutputPrinter see https://github.com/Behat/Behat/issues/1088
 */
class NullOutputPrinter implements OutputPrinter
{
    #[\Override]
    public function setOutputPath($path)
    {
    }

    #[\Override]
    public function getOutputPath()
    {
    }

    #[\Override]
    public function setOutputStyles(array $styles)
    {
    }

    #[\Override]
    public function getOutputStyles()
    {
    }

    #[\Override]
    public function setOutputDecorated($decorated)
    {
    }

    #[\Override]
    public function isOutputDecorated()
    {
    }

    #[\Override]
    public function setOutputVerbosity($level)
    {
    }

    #[\Override]
    public function getOutputVerbosity()
    {
    }

    #[\Override]
    public function write($messages)
    {
    }

    #[\Override]
    public function writeln($messages = '')
    {
    }

    #[\Override]
    public function flush()
    {
    }
}
