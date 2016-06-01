<?php
namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DoctrineClearIdentityMapExtension implements ExtensionInterface
{
    use ExtensionTrait;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        foreach ($this->registry->getManagers() as $manager) {
            $manager->clear();
        }
    }
}
