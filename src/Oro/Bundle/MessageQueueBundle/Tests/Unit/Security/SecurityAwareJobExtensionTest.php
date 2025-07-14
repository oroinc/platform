<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Security;

use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareJobExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\SecurityBundle\Exception\InvalidTokenSerializationException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SecurityAwareJobExtensionTest extends TestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private TokenSerializerInterface&MockObject $tokenSerializer;
    private JobManagerInterface&MockObject $jobManager;
    private SecurityAwareJobExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenSerializer = $this->createMock(TokenSerializerInterface::class);
        $this->jobManager = $this->createMock(JobManagerInterface::class);

        $this->extension = new SecurityAwareJobExtension(
            $this->tokenStorage,
            $this->tokenSerializer,
            $this->jobManager
        );
    }

    public function testOnPreRunUniqueShouldDoNothingIfNoSecurityTokenInTokenStorage(): void
    {
        $rootJob = new Job();
        $job = new Job();
        $job->setRootJob($rootJob);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->jobManager->expects(self::never())
            ->method('saveJob');

        $this->extension->onPreRunUnique($job);
        self::assertEmpty($rootJob->getProperties());
        self::assertEmpty($job->getProperties());
    }

    public function testOnPreRunUniqueShouldDoNothingIfRootJobAlreadyHasSecurityToken(): void
    {
        $rootJob = new Job();
        $job = new Job();
        $job->setRootJob($rootJob);

        $rootJobProperties = [SecurityAwareDriver::PARAMETER_SECURITY_TOKEN => 'token'];
        $rootJob->setProperties($rootJobProperties);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));

        $this->jobManager->expects(self::never())
            ->method('saveJob');

        $this->extension->onPreRunUnique($job);
        self::assertEquals($rootJobProperties, $rootJob->getProperties());
        self::assertEmpty($job->getProperties());
    }

    public function testOnPreRunUniqueShouldAddSecurityTokenToRootJobIfItIsNotSetYet(): void
    {
        $rootJob = new Job();
        $job = new Job();
        $job->setRootJob($rootJob);

        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'serialized';

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $this->tokenSerializer->expects(self::once())
            ->method('serialize')
            ->with(self::identicalTo($token))
            ->willReturn($serializedToken);

        $this->jobManager->expects(self::once())
            ->method('saveJob')
            ->with(self::identicalTo($rootJob));

        $this->extension->onPreRunUnique($job);
        self::assertEquals(
            [SecurityAwareDriver::PARAMETER_SECURITY_TOKEN => $serializedToken],
            $rootJob->getProperties()
        );
        self::assertEmpty($job->getProperties());
    }

    public function testOnPreRunUniqueShouldNotAddSecurityTokenToRootJobIfTokenCannotBeSerialized(): void
    {
        $rootJob = new Job();
        $job = new Job();
        $job->setRootJob($rootJob);

        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $this->tokenSerializer->expects(self::once())
            ->method('serialize')
            ->willThrowException(new InvalidTokenSerializationException('Exception message.'));

        $this->jobManager->expects(self::never())
            ->method('saveJob');

        $this->extension->onPreRunUnique($job);
        self::assertEmpty($rootJob->getProperties());
        self::assertEmpty($job->getProperties());
    }
}
