<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\ValidatorCacheWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Renews Symfony validator metadata cache.
 */
class ValidatorCacheClearCommand extends Command
{
    protected static $defaultName = 'validator:cache:clear';

    /** @var KernelInterface */
    private $kernel;

    /** @var ValidatorCacheWarmer */
    private $validatorCacheWarmer;

    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $validatorCacheFile;

    /**
     * @param KernelInterface      $kernel
     * @param ValidatorCacheWarmer $validatorCacheWarmer
     * @param Filesystem           $filesystem
     * @param string               $validatorCacheFile
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Clears the validator metadata cache.');
    }

    /**
     * {@inheritdoc}
     */
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
