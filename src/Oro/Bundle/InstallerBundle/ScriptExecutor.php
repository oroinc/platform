<?php

namespace Oro\Bundle\InstallerBundle;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ScriptExecutor
{
    const ORO_SCRIPT_ANNOTATION = 'OroScript';

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /**
     * @param OutputInterface    $output
     * @param ContainerInterface $container
     * @param CommandExecutor    $commandExecutor
     */
    public function __construct(
        OutputInterface $output,
        ContainerInterface $container,
        CommandExecutor $commandExecutor
    ) {
        $this->output          = $output;
        $this->container       = $container;
        $this->commandExecutor = $commandExecutor;
    }

    /**
     * Run script
     *
     * @param string $fileName
     */
    public function runScript($fileName)
    {
        if (is_file($fileName)) {
            $tokens = [];
            if (preg_match(
                '/@' . ScriptExecutor::ORO_SCRIPT_ANNOTATION . '\("([\w -]*)"\)/i',
                file_get_contents($fileName),
                $tokens
            )
            ) {
                $this->output->writeln(
                    sprintf('[%s] Launching "%s" script', date('Y-m-d H:i:s'), $fileName)
                );
                ob_start();
                $container       = $this->container;
                $commandExecutor = $this->commandExecutor;
                include($fileName);
                $scriptOutput = ob_get_contents();
                ob_clean();
                $this->output->writeln($scriptOutput);
            } else {
                $this->output->writeln(
                    'The "%s" script must contains @%s annotation',
                    $fileName,
                    ScriptExecutor::ORO_SCRIPT_ANNOTATION
                );
            }

        } else {
            $this->output->writeln('File "%s" not found', $fileName);
        }
    }
}
