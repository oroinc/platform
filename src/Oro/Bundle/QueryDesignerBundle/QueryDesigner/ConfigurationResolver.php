<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class ConfigurationResolver
{
    /**
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor
     *
     * @param EntityClassResolver $entityClassResolver
     * @param ContainerInterface  $container
     */
    public function __construct(EntityClassResolver $entityClassResolver, ContainerInterface $container)
    {
        $this->entityClassResolver = $entityClassResolver;
        $this->container           = $container;
    }

    /**
     * @param array $config
     */
    public function resolve(&$config)
    {
        array_walk_recursive(
            $config,
            function (&$val, $key) {
                if ($key === 'entity') {
                    $val = $this->entityClassResolver->getEntityClass($val);
                } elseif (is_string($val)) {
                    $this->resolveSystemCall($val);
                }
            }
        );
    }

    /**
     * Replace static call, service call or constant access notation to value they returned
     *
     * @param string $val value to be resolved/replaced
     *
     * @return string
     */
    protected function resolveSystemCall(&$val)
    {
        switch (true) {
            // static call class:method or class::const
            case preg_match('/^([^\'"%:\s]+)::([\w\._]+)$/', $val, $match):
                $class  = $match[1];
                $method = $match[2];
                if (is_callable([$class, $method])) {
                    $val = $class::$method();
                }
                if (defined("$class::$method")) {
                    $val = constant("$class::$method");
                }
                break;
            // service method call @service->method
            case preg_match('/^@([\w\._]+)->([\w\._]+)$/', $val, $match):
                $service = $match[1];
                $method  = $match[2];
                $val     = $this->container->get($service)->$method();
                break;
            // service pass @service
            case preg_match('/^@[\w\._]+$/', $val):
                $val = $this->container->get($val);
                break;
        }
    }
}
