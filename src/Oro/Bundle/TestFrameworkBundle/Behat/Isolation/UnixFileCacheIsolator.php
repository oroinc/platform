<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class UnixFileCacheIsolator extends OsRelatedIsolator implements IsolatorInterface
{
    /** @var string */
    protected $cacheDir;

    /** @var  string */
    protected $cacheDump;

    /** @var  Filesystem */
    protected $filesystem;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->cacheDir   = $kernel->getCacheDir();
        $this->cacheDump  = sys_get_temp_dir().DIRECTORY_SEPARATOR.'oro_cache_dump';
        $this->filesystem = new Filesystem();
    }

    /** {@inheritdoc} */
    public function start()
    {
        $this->filesystem->mirror($this->cacheDir, $this->cacheDump);
    }

    /** {@inheritdoc} */
    public function beforeTest()
    {}

    /** {@inheritdoc} */
    public function afterTest()
    {
        $this->filesystem->remove($this->cacheDir);
        $this->filesystem->mirror($this->cacheDump, $this->cacheDir);
    }

    /** {@inheritdoc} */
    public function terminate()
    {}

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return
            $this->isApplicableOS()
            && 'session.handler.native_file' == $container->getParameter('session_handler');
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            OsRelatedIsolator::LINUX_OS,
            OsRelatedIsolator::MAC_OS,
        ];
    }
}
