<?php

namespace Oro\Bundle\UserBundle\Tests\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\PasswordManager;

class PasswordManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userManager;

    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailProcessor;

    /**
     * @var int
     */
    protected $ttl = 86400;

    /**
     * @var PasswordManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $passwordManager;

    /**
     * @var User|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $user;

    protected function setUp()
    {
        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailProcessor = $this->getMockBuilder('Oro\Bundle\UserBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->passwordManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Security\PasswordManager')
            ->setConstructorArgs([$this->userManager, $this->mailProcessor, $this->ttl])
            ->setMethods(['setError'])
            ->getMock();
    }

    public function testErrorGetterSetter()
    {
        $this->passwordManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Security\PasswordManager')
            ->setConstructorArgs([$this->userManager, $this->mailProcessor, $this->ttl])
            ->setMethods(null)
            ->getMock();

        $this->assertFalse($this->passwordManager->hasError());
        $this->assertNull($this->passwordManager->getError());
    }

    /**
     * @param $mailProcessorHasErrorResult
     * @param $passwordManagerSetErrorCalls
     *
     * @dataProvider changePasswordFailProvider
     */
    public function testChangePasswordFail($mailProcessorHasErrorResult, $passwordManagerSetErrorCalls)
    {
        $this->mailProcessor->expects($this->once())
            ->method('sendChangePasswordEmail')
            ->willReturn(false);
        $this->mailProcessor->expects($this->once())
            ->method('hasError')
            ->willReturn($mailProcessorHasErrorResult);
        $this->passwordManager->expects($this->exactly($passwordManagerSetErrorCalls))
            ->method('setError');
        $this->user->expects($this->once())
            ->method('setPlainPassword');
        $this->user->expects($this->once())
            ->method('setPasswordChangedAt');

        $this->userManager->expects($this->never())
            ->method('updateUser');

        $passwordChanged = $this->passwordManager->changePassword($this->user, 'password');
        $this->assertFalse($passwordChanged);
    }

    /**
     * @return array
     */
    public function changePasswordFailProvider()
    {
        $data = [];

        $data[] = [true, 2];
        $data[] = [false, 1];

        return $data;
    }

    public function testChangePasswordSuccess()
    {
        $this->mailProcessor->expects($this->once())
            ->method('sendChangePasswordEmail')
            ->willReturn(true);
        $this->mailProcessor->expects($this->never())
            ->method('hasError');
        $this->passwordManager->expects($this->once())
            ->method('setError');
        $this->user->expects($this->once())
            ->method('setPlainPassword');
        $this->user->expects($this->once())
            ->method('setPasswordChangedAt');

        $this->userManager->expects($this->once())
            ->method('updateUser');

        $passwordChanged = $this->passwordManager->changePassword($this->user, 'password');
        $this->assertTrue($passwordChanged);
    }

    public function testResetPasswordTtlExpired()
    {
        $this->user->expects($this->once())
            ->method('isPasswordRequestNonExpired')
            ->willReturn(true);
        $this->passwordManager->expects($this->exactly(2))
            ->method('setError');
        $this->user->expects($this->never())
            ->method('getConfirmationToken');
        $this->mailProcessor->expects($this->never())
            ->method('sendResetPasswordAsAdminEmail');
        $this->mailProcessor->expects($this->never())
            ->method('sendResetPasswordEmail');
        $this->user->expects($this->never())
            ->method('setPasswordRequestedAt');
        $this->userManager->expects($this->never())
            ->method('updateUser');
        $this->mailProcessor->expects($this->never())
            ->method('hasError');

        $this->assertFalse($this->passwordManager->resetPassword($this->user, false));
    }

    /**
     * @param boolean       $asAdmin
     * @param int           $isPasswordRequestNonExpiredCalls
     * @param int           $setErrorCalls
     * @param boolean       $getConfirmationTokenResult
     * @param int           $setConfirmationTokenCalls
     * @param int           $generateTokenCalls
     * @param int           $sendResetPasswordEmailCalls
     * @param boolean       $sendResetPasswordEmailResult
     * @param int           $sendResetPasswordEmailAsAdminCalls
     * @param boolean       $sendResetPasswordEmailAsAdminResult
     * @param int           $setPasswordRequestedAtCalls
     * @param int           $updateUserCalls
     * @param int           $mailProcessorHasErrorCalls
     * @param boolean       $mailProcessorHasErrorResult
     * @param int           $mailProcessorGetErrorCalls
     * @param boolean       $expectedResult
     *
     * @dataProvider resetPasswordProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testResetPassword(
        $asAdmin,
        $isPasswordRequestNonExpiredCalls,
        $setErrorCalls,
        $getConfirmationTokenResult,
        $setConfirmationTokenCalls,
        $generateTokenCalls,
        $sendResetPasswordEmailCalls,
        $sendResetPasswordEmailResult,
        $sendResetPasswordEmailAsAdminCalls,
        $sendResetPasswordEmailAsAdminResult,
        $setPasswordRequestedAtCalls,
        $updateUserCalls,
        $mailProcessorHasErrorCalls,
        $mailProcessorHasErrorResult,
        $mailProcessorGetErrorCalls,
        $expectedResult
    ) {
        $this->user->expects($this->exactly($isPasswordRequestNonExpiredCalls))
            ->method('isPasswordRequestNonExpired')
            ->willReturn(false);
        $this->passwordManager->expects($this->exactly($setErrorCalls))
            ->method('setError');
        $this->user->expects($this->once())
            ->method('getConfirmationToken')
            ->willReturn($getConfirmationTokenResult);
        $this->user->expects($this->exactly($setConfirmationTokenCalls))
            ->method('setConfirmationToken');
        $this->user->expects($this->exactly($generateTokenCalls))
            ->method('generateToken');
        $this->mailProcessor->expects($this->exactly($sendResetPasswordEmailCalls))
            ->method('sendResetPasswordEmail')
            ->willReturn($sendResetPasswordEmailResult);
        $this->mailProcessor->expects($this->exactly($sendResetPasswordEmailAsAdminCalls))
            ->method('sendResetPasswordAsAdminEmail')
            ->willReturn($sendResetPasswordEmailAsAdminResult);
        $this->user->expects($this->exactly($setPasswordRequestedAtCalls))
            ->method('setPasswordRequestedAt');
        $this->userManager->expects($this->exactly($updateUserCalls))
            ->method('updateUser');
        $this->mailProcessor->expects($this->exactly($mailProcessorHasErrorCalls))
            ->method('hasError')
            ->willReturn($mailProcessorHasErrorResult);
        $this->mailProcessor->expects($this->exactly($mailProcessorGetErrorCalls))
            ->method('getError');

        $this->assertEquals($expectedResult, $this->passwordManager->resetPassword($this->user, $asAdmin));
    }

    public function resetPasswordProvider()
    {
        $sendingFailedAsAdmin = [
            'asAdmin' => true,
            'isPasswordRequestNonExpiredCalls' => 0,
            'setErrorCalls' => 2,
            'getConfirmationTokenResult' => null,
            'setConfirmationTokenCalls' => 1,
            'generateTokenCalls' => 1,
            'sendResetPasswordEmailCalls' => 0,
            'sendResetPasswordEmailResult' => false,
            'sendResetPasswordEmailAsAdminCalls' => 1,
            'sendResetPasswordEmailAsAdminResult' => false,
            'setPasswordRequestedAtCalls' => 0,
            'updateUserCalls' => 0,
            'mailProcessorHasErrorCalls' => 1,
            'mailProcessorHasErrorResult' => true,
            'mailProcessorGetErrorCalls' => 1,
            'expectedResult' => false,
        ];

        $sendingFailedAsUser = [
            'asAdmin' => false,
            'isPasswordRequestNonExpiredCalls' => 1,
            'setErrorCalls' => 1,
            'getConfirmationTokenResult' => true,
            'setConfirmationTokenCalls' => 0,
            'generateTokenCalls' => 0,
            'sendResetPasswordEmailCalls' => 1,
            'sendResetPasswordEmailResult' => false,
            'sendResetPasswordEmailAsAdminCalls' => 0,
            'sendResetPasswordEmailAsAdminResult' => false,
            'setPasswordRequestedAtCalls' => 0,
            'updateUserCalls' => 0,
            'mailProcessorHasErrorCalls' => 1,
            'mailProcessorHasErrorResult' => false,
            'mailProcessorGetErrorCalls' => 0,
            'expectedResult' => false,
        ];

        $sendingSuccessAsAdmin = [
            'asAdmin' => true,
            'isPasswordRequestNonExpiredCalls' => 0,
            'setErrorCalls' => 1,
            'getConfirmationTokenResult' => true,
            'setConfirmationTokenCalls' => 0,
            'generateTokenCalls' => 0,
            'sendResetPasswordEmailCalls' => 0,
            'sendResetPasswordEmailResult' => false,
            'sendResetPasswordEmailAsAdminCalls' => 1,
            'sendResetPasswordEmailAsAdminResult' => true,
            'setPasswordRequestedAtCalls' => 1,
            'updateUserCalls' => 1,
            'mailProcessorHasErrorCalls' => 0,
            'mailProcessorHasErrorResult' => false,
            'mailProcessorGetErrorCalls' => 0,
            'expectedResult' => true,
        ];

        $sendingSuccessAsUser = [
            'asAdmin' => false,
            'isPasswordRequestNonExpiredCalls' => 1,
            'setErrorCalls' => 1,
            'getConfirmationTokenResult' => null,
            'setConfirmationTokenCalls' => 1,
            'generateTokenCalls' => 1,
            'sendResetPasswordEmailCalls' => 1,
            'sendResetPasswordEmailResult' => true,
            'sendResetPasswordEmailAsAdminCalls' => 0,
            'sendResetPasswordEmailAsAdminResult' => false,
            'setPasswordRequestedAtCalls' => 1,
            'updateUserCalls' => 1,
            'mailProcessorHasErrorCalls' => 0,
            'mailProcessorHasErrorResult' => false,
            'mailProcessorGetErrorCalls' => 0,
            'expectedResult' => true,
        ];

        return compact('sendingFailedAsAdmin', 'sendingFailedAsUser', 'sendingSuccessAsAdmin', 'sendingSuccessAsUser');
    }
}
