<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dispatched after all Behat tests have finished execution.
 *
 * This event allows isolators to perform cleanup operations after the entire test suite completes,
 * such as removing temporary files, restoring system state, or generating reports.
 */
final class AfterFinishTestsEvent implements TestIsolationEvent
{
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines of a single string
     * @param int          $options  A bitmask of options (one of the OUTPUT or VERBOSITY constants),
     *                     0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function writeln($messages, $options = 0)
    {
        return $this->output->writeln($messages, $options);
    }
}
