<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Api\Processor\Create;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\UserBundle\Api\Processor\Create\SaveEntity;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class SaveEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $userManager;

    /** @var SaveEntity */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->userManager = $this->getMockBuilder(UserManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SaveEntity($this->userManager);
    }

    public function testProcessWhenNoResult()
    {
        $this->userManager->expects(self::never())
            ->method('updateUser');

        $this->processor->process($this->context);
    }

    public function testProcessWhenResultIsNotObject()
    {
        $user = [];

        $this->userManager->expects(self::never())
            ->method('updateUser');

        $this->context->setResult($user);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultIsObject()
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $plainPassword = 'some_password';

        $user->expects(self::once())
            ->method('setPlainPassword')
            ->with($plainPassword);

        $this->userManager->expects(self::once())
            ->method('generatePassword')
            ->willReturn($plainPassword);
        $this->userManager->expects(self::once())
            ->method('updateUser')
            ->with(self::identicalTo($user));

        $this->context->setResult($user);
        $this->processor->process($this->context);
    }

    public function testPasswordAlreadySet()
    {
        $user = $this->getMockBuilder(User::class)
            ->getMock();
        $user->expects(self::once())
            ->method('getPassword')
            ->willReturn('test_password');

        $user->expects(self::never())
            ->method('setPlainPassword');

        $this->userManager->expects(self::never())
            ->method('generatePassword');

        $this->context->setResult($user);
        $this->processor->process($this->context);
    }
}
