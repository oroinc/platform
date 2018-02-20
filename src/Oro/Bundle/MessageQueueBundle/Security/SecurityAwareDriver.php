<?php

namespace Oro\Bundle\MessageQueueBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * This class can be used to wrap a specific driver in order to add the current security token to a message.
 * For details see "Resources/doc/secutity_context.md".
 */
class SecurityAwareDriver implements DriverInterface
{
    const PARAMETER_SECURITY_TOKEN = 'oro.security.token';

    /** @var DriverInterface */
    private $driver;

    /** @var array [topic name => TRUE, ...] */
    private $securityAgnosticTopics;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TokenSerializerInterface */
    private $tokenSerializer;

    /**
     * @param DriverInterface          $driver
     * @param string[]                 $securityAgnosticTopics
     * @param TokenStorageInterface    $tokenStorage
     * @param TokenSerializerInterface $tokenSerializer
     */
    public function __construct(
        DriverInterface $driver,
        array $securityAgnosticTopics,
        TokenStorageInterface $tokenStorage,
        TokenSerializerInterface $tokenSerializer
    ) {
        $this->driver = $driver;
        $this->securityAgnosticTopics = array_fill_keys($securityAgnosticTopics, true);
        $this->tokenStorage = $tokenStorage;
        $this->tokenSerializer = $tokenSerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function send(QueueInterface $queue, Message $message)
    {
        // add the current security token to the message
        // if it exists in the current security context
        // and it was not set to the message yet
        // for messages are sent to security agnostic topics the security token is never added
        $topicName = $message->getProperty(Config::PARAMETER_TOPIC_NAME);
        $properties = $message->getProperties();
        if (array_key_exists(self::PARAMETER_SECURITY_TOKEN, $properties)) {
            if (isset($this->securityAgnosticTopics[$topicName])) {
                // remove existing token if the topic is security agnostic
                unset($properties[self::PARAMETER_SECURITY_TOKEN]);
                $message->setProperties($properties);
            } else {
                // serialize existing token if needed
                $token = $properties[self::PARAMETER_SECURITY_TOKEN];
                if ($token instanceof TokenInterface) {
                    unset($properties[self::PARAMETER_SECURITY_TOKEN]);
                    $serializedToken = $this->tokenSerializer->serialize($token);
                    if (null !== $serializedToken) {
                        $properties[self::PARAMETER_SECURITY_TOKEN] = $serializedToken;
                    }
                    $message->setProperties($properties);
                }
            }
        } elseif (!isset($this->securityAgnosticTopics[$topicName])) {
            // add the current token if it exists
            $token = $this->tokenStorage->getToken();
            if ($token instanceof TokenInterface) {
                $serializedToken = $this->tokenSerializer->serialize($token);
                if (null !== $serializedToken) {
                    $properties[self::PARAMETER_SECURITY_TOKEN] = $serializedToken;
                    $message->setProperties($properties);
                }
            }
        }

        $this->driver->send($queue, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function createTransportMessage()
    {
        return $this->driver->createTransportMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        return $this->driver->createQueue($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->driver->getConfig();
    }
}
