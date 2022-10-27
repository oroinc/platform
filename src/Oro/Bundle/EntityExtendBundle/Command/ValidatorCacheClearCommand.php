<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\ValidatorCacheWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Clears validator metadata cache.
 */
class ValidatorCacheClearCommand extends Command
{
    protected static $defaultName = 'validator:cache:clear';

    private KernelInterface $kernel;
    private ValidatorCacheWarmer $validatorCacheWarmer;
    private Filesystem $filesystem;
    private string $validatorCacheFile;

    public function __construct(
        KernelInterface $kernel,
        ValidatorCacheWarmer $validatorCacheWarmer,
        Filesystem $filesystem,
        string $validatorCacheFile
    ) {
        $this->kernel = $kernel;
        $this->validatorCacheWarmer = $validatorCacheWarmer;
        $this->filesystem = $filesystem;
        $this->validatorCacheFile = $validatorCacheFile;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheDir = $this->kernel->getCacheDir();

        if (!is_writable($cacheDir)) {
            throw new \RuntimeException(sprintf('Unable to write in the "%s" directory.', $cacheDir));
        }

        $io = new SymfonyStyle($input, $output);
        $io->text(sprintf(
            'Clearing the validator metadata cache for the <info>%s</info> environment...',
            $this->kernel->getEnvironment()
        ));

        if ($this->filesystem->exists($this->validatorCacheFile)) {
            $this->filesystem->remove($this->validatorCacheFile);
        }
        $this->validatorCacheWarmer->warmUp($cacheDir);

        $io->success('The cache was successfully cleared.');

        return 0;
    }
}
