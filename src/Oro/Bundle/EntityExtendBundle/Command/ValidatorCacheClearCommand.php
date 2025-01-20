<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\ValidatorCacheWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Clears validator metadata cache.
 */
class ValidatorCacheClearCommand extends Command
{
    protected static $defaultName = 'validator:cache:clear';

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly ValidatorCacheWarmer $validatorCacheWarmer,
    ) {
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Clears validator metadata cache.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears validator metadata cache.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheDir = $this->kernel->getCacheDir();

        $io = new SymfonyStyle($input, $output);
        $io->text(sprintf(
            'Clearing the validator metadata cache for the <info>%s</info> environment...',
            $this->kernel->getEnvironment()
        ));
        $this->validatorCacheWarmer->warmUp($cacheDir);

        $io->success('The cache was successfully cleared.');

        return Command::SUCCESS;
    }
}
