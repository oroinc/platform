<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractDbDumper implements DbDumperInterface
{
    /** @var string */
    protected $dbHost;

    /** @var string */
    protected $dbName;

    /** @var string */
    protected $dbPass;

    /** @var string */
    protected $dbUser;

    /** @var string */
    protected $cacheDir;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->cacheDir = $kernel->getCacheDir();
        $this->dbHost = $container->getParameter('database_host');
        $this->dbName = $container->getParameter('database_name');
        $this->dbUser = $container->getParameter('database_user');
        $this->dbPass = $container->getParameter('database_password');
    }
}
