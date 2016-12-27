<?php

namespace Oro\Bundle\CronBundle\Engine;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandRunner implements CommandRunnerInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param string $commandName
     * @param array $commandArguments
     *
     * @return string
     */
    public function run($commandName, $commandArguments = [])
    {
        if (! $commandArguments) {
            $commandArguments = [];
        }
        if ($commandArguments && ! is_array($commandArguments)) {
            $commandArguments = [$commandArguments];
        }

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array_merge(['command' => $commandName], $commandArguments));

        $output = new BufferedOutput();
        $application->run($input, $output);

        return $output->fetch();
    }
}
