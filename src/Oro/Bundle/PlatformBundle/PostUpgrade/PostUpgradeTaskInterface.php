<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\PostUpgrade;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface for tasks that should be executed asynchronously after upgrade
 */
interface PostUpgradeTaskInterface
{
    /**
     * Unique task name (e.g. 'product_fallback')
     * Used to run specific task via --task=NAME
     */
    public function getName(): string;

    /**
     * Task description for console output
     */
    public function getDescription(): string;

    /**
     * Execute task asynchronously
     *
     * Tasks can read any options from InputInterface, write progress/output via OutputInterface or SymfonyStyle.
     * This allows each task to define its own specific options and provide detailed output.
     *
     * @return PostUpgradeTaskResult Execution result
     */
    public function execute(InputInterface $input, OutputInterface $output, SymfonyStyle $io): PostUpgradeTaskResult;
}
