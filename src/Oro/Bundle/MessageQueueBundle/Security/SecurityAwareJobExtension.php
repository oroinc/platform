<?php

namespace Oro\Bundle\MessageQueueBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Job\Extension\AbstractExtension;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Adds the current security token to the root job.
 */
class SecurityAwareJobExtension extends AbstractExtension
{
    private TokenStorageInterface $tokenStorage;
    private TokenSerializerInterface $tokenSerializer;
    private JobManagerInterface $jobManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TokenSerializerInterface $tokenSerializer,
        JobManagerInterface $jobManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->tokenSerializer = $tokenSerializer;
        $this->jobManager = $jobManager;
    }

    /**
     * {@inheritDoc}
     */
    public function onPreRunUnique(Job $job): void
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof TokenInterface) {
            return;
        }

        if (!$job->isRoot()) {
            $job = $job->getRootJob();
        }
        $jobProperties = $job->getProperties();
        if (\array_key_exists(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, $jobProperties)) {
            return;
        }

        $serializedToken = $this->tokenSerializer->serialize($token);
        if (null !== $serializedToken) {
            $jobProperties[SecurityAwareDriver::PARAMETER_SECURITY_TOKEN] = $serializedToken;
            $job->setProperties($jobProperties);
            $this->jobManager->saveJob($job);
        }
    }
}
