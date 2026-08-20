<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\UserBundle\Async\Topic\UserPasswordResetRequestTopic;
use Oro\Bundle\UserBundle\Form\Handler\UserPasswordResetHandler;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UserPasswordResetHandlerTest extends TestCase
{
    use MessageQueueExtension;

    private UserLoggingInfoProviderInterface&MockObject $userLoggingInfoProvider;
    private LoggerInterface&MockObject $logger;
    private UserPasswordResetHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->userLoggingInfoProvider = $this->createMock(UserLoggingInfoProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new UserPasswordResetHandler(
            self::getMessageProducer(),
            $this->userLoggingInfoProvider,
            $this->logger
        );
    }

    public function testProcessWithGetRequest(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => Request::METHOD_GET]);
        $form = $this->createMock(FormInterface::class);

        $form->expects(self::never())
            ->method('handleRequest');

        self::assertNull($this->handler->process($form, $request));
        self::assertMessagesEmpty(UserPasswordResetRequestTopic::getName());
    }

    public function testProcessWithNotSubmittedForm(): void
    {
        $request = $this->getRequest();
        $form = $this->createMock(FormInterface::class);

        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        self::assertNull($this->handler->process($form, $request));
        self::assertMessagesEmpty(UserPasswordResetRequestTopic::getName());
    }

    public function testProcessWithInvalidForm(): void
    {
        $request = $this->getRequest();
        $form = $this->createMock(FormInterface::class);

        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        self::assertNull($this->handler->process($form, $request));
        self::assertMessagesEmpty(UserPasswordResetRequestTopic::getName());
    }

    /**
     * @dataProvider userIdentifierDataProvider
     */
    public function testProcessSchedulesProcessingOfUserIdentifier(string $userIdentifier): void
    {
        $request = $this->getRequest();
        $form = $this->configureValidSubmittedForm($request, $userIdentifier);

        $this->userLoggingInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($userIdentifier)
            ->willReturn(['username' => $userIdentifier, 'ipaddress' => '127.0.0.1']);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with(
                'Reset password email has been requested.',
                ['username' => $userIdentifier, 'ipaddress' => '127.0.0.1']
            );

        self::assertEquals($userIdentifier, $this->handler->process($form, $request));
        self::assertMessageSent(
            UserPasswordResetRequestTopic::getName(),
            [UserPasswordResetRequestTopic::USER_IDENTIFIER => $userIdentifier]
        );
    }

    public function userIdentifierDataProvider(): array
    {
        return [
            'existing user' => ['userIdentifier' => 'existing_user'],
            'non-existing user' => ['userIdentifier' => 'nonexistent_user'],
            'email' => ['userIdentifier' => 'test@example.com'],
        ];
    }

    private function getRequest(): Request
    {
        return new Request([], [], [], [], [], ['REQUEST_METHOD' => Request::METHOD_POST]);
    }

    private function configureValidSubmittedForm(
        Request $request,
        string $username
    ): FormInterface&MockObject {
        $form = $this->createMock(FormInterface::class);

        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $form->expects(self::once())
            ->method('get')
            ->with('username')
            ->willReturn($this->createConfiguredMock(FormInterface::class, ['getData' => $username]));

        return $form;
    }
}
