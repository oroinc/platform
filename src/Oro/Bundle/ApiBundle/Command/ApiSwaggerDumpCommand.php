<?php

namespace Oro\Bundle\ApiBundle\Command;

use Nelmio\ApiDocBundle\Command\SwaggerDumpCommand as NelmioSwaggerDumpCommand;
use Psr\Container\ContainerInterface;

/**
 * Wraps "api:swagger:dump" from NelmioApiDocBundle to make it work in Symfony4.
 */
class ApiSwaggerDumpCommand extends NelmioSwaggerDumpCommand
{
    protected static $defaultName = 'api:swagger:dump';

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
