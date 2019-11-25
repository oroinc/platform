<?php

namespace Oro\Bundle\ApiBundle\Command;

use Nelmio\ApiDocBundle\Command\DumpCommand as NelmioDumpCommand;
use Psr\Container\ContainerInterface;

/**
 * Wraps "api:doc:dump" from NelmioApiDocBundle to make it work in Symfony4.
 */
class ApiDocDumpCommand extends NelmioDumpCommand
{
    protected static $defaultName = 'api:doc:dump';

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}
