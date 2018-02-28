<?php

namespace Oro\Bundle\MessageQueueBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The message consumption extension that replaces a current security token with the token
 * that is contained in a current message.
 * This provides an ability to process a message in the same security context
 * as a process that sent the message.
 * Also the "security_agnostic_processors" option can be used to disable changing the security context
 * for some processors.
 * For details see "Resources/doc/secutity_context.md".
 */
class SecurityAwareConsumptionExtension extends AbstractExtension
{
    /** @var array [processor name => TRUE, ...] */
    private $securityAgnosticProcessors;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TokenSerializerInterface */
    private $tokenSerializer;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param string[]                 $securityAgnosticProcessors
     * @param TokenStorageInterface    $tokenStorage
     * @param TokenSerializerInterface $tokenSerializer
     * @param LoggerInterface          $logger
     */
    public function __construct(
        array $securityAgnosticProcessors,
        TokenStorageInterface $tokenStorage,
        TokenSerializerInterface $tokenSerializer,
        LoggerInterface $logger
    ) {
        $this->securityAgnosticProcessors = array_fill_keys($securityAgnosticProcessors, true);
        $this->tokenStorage = $tokenStorage;
        $this->tokenSerializer = $tokenSerializer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        if (isset($this->securityAgnosticProcessors[$this->getProcessorName($context)])) {
            return;
        }

        // check whether a current message should be executed in own security context,
        // and if so, switch to the requested context
        $serializedToken = $context->getMessage()->getProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN);
        if ($serializedToken) {
            $token = $this->tokenSerializer->deserialize($serializedToken);
            if (null === $token) {
                $this->logger->error('Cannot deserialize security token');
                $context->setStatus(MessageProcessorInterface::REJECT);
            } else {
                $this->logger->debug('Set security token');
                $this->tokenStorage->setToken($token);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        // reset the security context after processing of each message
        $this->tokenStorage->setToken(null);
    }

    /**
     * @param Context $context
     *
     * @return string
     */
    private function getProcessorName(Context $context)
    {
        return $context->getMessage()->getProperty(Config::PARAMETER_PROCESSOR_NAME);
    }
}
