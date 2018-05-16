<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

class ImportExportIsolator implements IsolatorInterface
{
    /** @var Filesystem */
    protected $fs;

    /** @var Finder */
    protected $finder;

    /** @var string */
    protected $path;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->fs = new Filesystem();
        $this->finder = new Finder();
        $this->path = $kernel->getProjectDir().DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'import_export';
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $this->fs->remove($this->finder->files()->in($this->path));
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $this->fs->remove($this->finder->files()->in($this->path));
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return $this->fs->exists($this->path);
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        $this->fs->remove($this->finder->files()->in($this->path));
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        return (bool)$this->finder->files()->in($this->path)->count();
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return 'Import and Export isolator';
    }

    /** {@inheritdoc} */
    public function getTag()
    {
        return 'import_export';
    }
}
