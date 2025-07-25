<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Clears router cache.
 */
#[AsCommand(
    name: 'router:cache:clear',
    description: 'Clears router cache.'
)]
class RouterCacheClearCommand extends Command
{
    private KernelInterface $kernel;
    private RouterInterface $router;
    private Filesystem $filesystem;

    public function __construct(
        KernelInterface $kernel,
        RouterInterface $router,
        Filesystem $filesystem
    ) {
        $this->kernel = $kernel;
        $this->router = $router;
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return
            $this->router instanceof WarmableInterface
            && parent::isEnabled();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears router cache.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cacheDir = $this->kernel->getCacheDir();
        $tmpDir = $cacheDir . '_tmp';

        try {
            if (!is_writable($cacheDir)) {
                throw new \RuntimeException(sprintf('Unable to write in the "%s" directory.', $cacheDir));
            }

            $io->text(sprintf(
                'Clearing the routing cache for the <info>%s</info> environment...',
                $this->kernel->getEnvironment()
            ));

            $this->ensureDirNotExists($tmpDir);
            $this->filesystem->mkdir($tmpDir);
            $this->router->warmUp($tmpDir);
            $this->moveToCacheDir($cacheDir, $tmpDir);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return $e->getCode() ?: 1;
        } finally {
            $this->ensureDirNotExists($tmpDir);
        }

        $io->success('The cache was successfully cleared.');

        return Command::SUCCESS;
    }

    private function ensureDirNotExists(string $dir): void
    {
        if ($this->filesystem->exists($dir)) {
            $this->filesystem->remove($dir);
        }
    }

    private function moveToCacheDir(string $cacheDir, string $tmpDir): void
    {
        /** @var SplFileInfo[] $files */
        $files = Finder::create()->files()->in($tmpDir);
        foreach ($files as $file) {
            $this->filesystem->copy(
                $file->getPathname(),
                $cacheDir . DIRECTORY_SEPARATOR . $file->getFilename()
            );
        }

        $this->filesystem->remove($tmpDir);
    }
}
