<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

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
 * Renews Symfony routing cache.
 */
class RouterCacheClearCommand extends Command
{
    protected static $defaultName = 'router:cache:clear';

    /** @var KernelInterface */
    private $kernel;

    /** @var RouterInterface */
    private $router;

    /** @var Filesystem */
    private $filesystem;

    /**
     * @param KernelInterface $kernel
     * @param RouterInterface $router
     * @param Filesystem      $filesystem
     */
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

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return
            $this->router instanceof WarmableInterface
            && parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Clears the routing cache.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When route does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheDir = $this->kernel->getCacheDir();
        $tmpDir = $cacheDir . '_tmp';

        if (!is_writable($cacheDir)) {
            throw new \RuntimeException(sprintf('Unable to write in the "%s" directory.', $cacheDir));
        }

        $io = new SymfonyStyle($input, $output);
        $io->text(sprintf(
            'Clearing the routing cache for the <info>%s</info> environment...',
            $this->kernel->getEnvironment()
        ));

        $this->ensureDirNotExists($tmpDir);
        $this->filesystem->mkdir($tmpDir);
        try {
            $this->router->warmUp($tmpDir);
            $this->moveToCacheDir($cacheDir, $tmpDir);
        } finally {
            $this->ensureDirNotExists($tmpDir);
        }

        $io->success('The cache was successfully cleared.');

        return 0;
    }

    /**
     * @param string $dir
     */
    private function ensureDirNotExists(string $dir): void
    {
        if ($this->filesystem->exists($dir)) {
            $this->filesystem->remove($dir);
        }
    }

    /**
     * @param string $cacheDir
     * @param string $tmpDir
     */
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
