<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Renew Symfony routing cache.
 */
class RouterCacheClearCommand extends Command
{
    protected static $defaultName = 'router:cache:clear';

    /** @var KernelInterface */
    private $kernel;

    /** @var RouterInterface|null */
    private $router;

    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $cacheDir;

    /**
     * @param KernelInterface $kernel
     * @param RouterInterface|null $router
     * @param Filesystem $filesystem
     * @param string $cacheDir
     */
    public function __construct(
        KernelInterface $kernel,
        ?RouterInterface $router,
        Filesystem $filesystem,
        string $cacheDir
    ) {
        $this->kernel = $kernel;
        $this->router = $router;
        $this->filesystem = $filesystem;
        $this->cacheDir = $cacheDir;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!$this->router) {
            return false;
        }

        if (!$this->router instanceof WarmableInterface) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Clears the routing cache for an application')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> clears the routing cache for a given environment:

  <info>php %command.full_name% --env=prod</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When route does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tmpCacheDir  = $this->cacheDir . '_tmp';

        if (!is_writable($this->cacheDir)) {
            throw new \RuntimeException(sprintf('Unable to write in the "%s" directory', $this->cacheDir));
        }

        if ($this->filesystem->exists($tmpCacheDir)) {
            $this->filesystem->remove($tmpCacheDir);
        }

        $output->writeln(
            sprintf(
                'Clearing the routing cache for the <info>%s</info> environment',
                $this->kernel->getEnvironment()
            )
        );

        $this->router->warmUp($tmpCacheDir);

        /** @var SplFileInfo $file */
        foreach (Finder::create()->files()->in($tmpCacheDir) as $file) {
            $this->filesystem->copy(
                $file->getPathname(),
                $this->cacheDir . DIRECTORY_SEPARATOR . $file->getFilename()
            );
        }

        $this->filesystem->remove($tmpCacheDir);
    }
}
