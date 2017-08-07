<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event;

use Behat\Gherkin\Node\TaggedNodeInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class BeforeIsolatedTestEvent implements TestIsolationEvent
{
    /**
     * @var TaggedNodeInterface
     */
    protected $test;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param TaggedNodeInterface $test
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output, TaggedNodeInterface $test = null)
    {
        $this->output = $output;
        $this->test = $test;
    }

    /**
     * @return \string[]
     */
    public function getTags()
    {
        return $this->test->getTags();
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines of a single string
     * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants),
     *                     0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function writeln($messages, $options = 0)
    {
        return $this->output->writeln($messages, $options);
    }
}
