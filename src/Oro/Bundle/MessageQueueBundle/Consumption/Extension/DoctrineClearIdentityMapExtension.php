<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\IntrospectableContainerInterface;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class DoctrineClearIdentityMapExtension extends AbstractExtension
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var ContainerInterface|IntrospectableContainerInterface */
    private $container;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ContainerInterface $container
     * @deprecated since 2.0
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $logger = $context->getLogger();
        $managers = $this->registry->getManagerNames();
        foreach ($managers as $name => $serviceId) {
            if ($this->container->initialized($serviceId)) {
                $logger->debug(sprintf(
                    '[DoctrineClearIdentityMapExtension] Clear identity map for manager "%s"',
                    $name
                ));

                $manager = $this->registry->getManager($name);
                $manager->clear();
            }
        }
    }
}
