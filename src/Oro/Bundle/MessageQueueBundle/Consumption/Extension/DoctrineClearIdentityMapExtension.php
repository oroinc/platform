<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrineClearIdentityMapExtension extends AbstractExtension implements ResettableExtensionInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var ManagerRegistry|null */
    private $doctrine;

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
    public function reset()
    {
        $this->doctrine = null;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        if (null === $this->doctrine) {
            $this->doctrine = $this->container->get('doctrine');
        }

        $logger = $context->getLogger();
        $managers = $this->doctrine->getManagerNames();
        foreach ($managers as $name => $serviceId) {
            if ($this->container->initialized($serviceId)) {
                $logger->debug(sprintf('Clear identity map for manager "%s"', $name));

                $manager = $this->doctrine->getManager($name);
                $manager->clear();
            }
        }
    }
}
