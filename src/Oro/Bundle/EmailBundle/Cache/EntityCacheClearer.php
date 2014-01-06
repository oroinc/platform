<?php

namespace Oro\Bundle\EmailBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

use Symfony\Component\Filesystem\Filesystem;

class EntityCacheClearer implements CacheClearerInterface
{
    /**
     * @var string
     */
    private $entityCacheDir;

    /**
     * @var string
     */
    private $entityProxyNameTemplate;

    /**
     * Constructor.
     *
     * @param string $entityCacheDir
     * @param string $entityProxyNameTemplate
     */
    public function __construct($entityCacheDir, $entityProxyNameTemplate)
    {
        $this->entityCacheDir = $entityCacheDir;
        $this->entityProxyNameTemplate = $entityProxyNameTemplate;
    }

    /**
     * {inheritdoc}
     */
    public function clear($cacheDir)
    {
        $fs = $this->createFilesystem();
        $this->removeEmailAddressProxy($fs);
    }

    /**
     * Create Filesystem object
     *
     * @return Filesystem
     */
    protected function createFilesystem()
    {
        return new Filesystem();
    }

    /**
     * Removes a proxy class for EmailAddress entity
     *
     * @param Filesystem $fs
     */
    protected function removeEmailAddressProxy(Filesystem $fs)
    {
        $className = sprintf($this->entityProxyNameTemplate, 'EmailAddress');

        // remove a proxy class
        $fs->remove(sprintf('%s%s%s.php', $this->entityCacheDir, DIRECTORY_SEPARATOR, $className));
        // remove ORM mappings for a proxy class
        $fs->remove(sprintf('%s%s%s.orm.yml', $this->entityCacheDir, DIRECTORY_SEPARATOR, $className));
    }
}
