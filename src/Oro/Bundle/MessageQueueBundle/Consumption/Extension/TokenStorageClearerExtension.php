<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * @deprecated since 2.0
 * @see \Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerResetExtension
 */
class TokenStorageClearerExtension extends AbstractExtension
{
    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
    }

    /**
     * @param Context $context
     */
    public function onPostReceived(Context $context)
    {
    }
}
