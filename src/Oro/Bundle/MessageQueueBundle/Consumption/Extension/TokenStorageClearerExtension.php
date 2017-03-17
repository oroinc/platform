<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class TokenStorageClearerExtension extends AbstractExtension
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Context $context
     */
    public function onPostReceived(Context $context)
    {
        $this->tokenStorage->setToken(null);
    }
}
