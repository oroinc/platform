<?php


namespace Oro\Bundle\AsseticBundle\Command\Proxy;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ContainerProxy implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $replacedServices = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function initialized($id)
    {
        return $this->container->initialized($id);
    }

    /**
     * Replaces existing service with new implementation.
     *
     * @param string $id      The service identifier
     * @param object $service The service instance
     *
     * @throws ServiceNotFoundException When the service is not defined
     */
    public function replace($id, $service)
    {
        if (!$this->has($id)) {
            throw new ServiceNotFoundException($id);
        }
        $this->replacedServices[$id] = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function set($id, $service)
    {
        $this->container->set($id, $service);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        return isset($this->replacedServices[$id])
            ? $this->replacedServices[$id]
            : $this->container->get($id, $invalidBehavior);
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter($name)
    {
        return $this->container->getParameter($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameter($name)
    {
        return $this->container->hasParameter($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter($name, $value)
    {
        $this->container->setParameter($name, $value);
    }
}
