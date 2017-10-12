<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Output\Printer;

use Behat\Testwork\Output\Printer\OutputPrinter;

/**
 * Fake OutputPrinter
 * Unable to create DbOutputPrinter see https://github.com/Behat/Behat/issues/1088
 */
class NullOutputPrinter implements OutputPrinter
{
    /**
     * {@inheritdoc}
     */
    public function setOutputPath($path)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputPath()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setOutputStyles(array $styles)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputStyles()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setOutputDecorated($decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isOutputDecorated()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setOutputVerbosity($level)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputVerbosity()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function writeln($messages = '')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
    }
}
