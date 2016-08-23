<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\UserBundle\Entity\User;

class ApplicationsHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface */
    protected $tokenStorage;

    /** @var ApplicationsHelper */
    protected $helper;

    protected function setUp()
    {
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->helper = new ApplicationsHelper($this->tokenStorage);
    }

    protected function tearDown()
    {
        unset($this->helper, $this->tokenStorage);
    }

    public function testGetWidgetRoute()
    {
        $this->assertEquals('oro_action_widget_buttons', $this->helper->getWidgetRoute());
    }

    public function testGetDialogRoute()
    {
        $this->assertEquals('oro_action_widget_form', $this->helper->getDialogRoute());
    }

    public function testGetExecutionRoute()
    {
        $this->assertEquals('oro_action_operation_execute', $this->helper->getExecutionRoute());
    }

    /**
     * @dataProvider isApplicationsValidDataProvider
     *
     * @param array $applications
     * @param TokenInterface|null $token
     * @param bool $expectedResult
     */
    public function testIsApplicationsValid(array $applications, $token, $expectedResult)
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($expectedResult, $this->helper->isApplicationsValid($this->createAction($applications)));
    }

    /**
     * @dataProvider getCurrentApplicationProvider
     *
     * @param TokenInterface|null $token
     * @param string $expectedResult
     */
    public function testGetCurrentApplication($token, $expectedResult)
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->assertSame($expectedResult, $this->helper->getCurrentApplication());
    }

    /**
     * @return array
     */
    public function isApplicationsValidDataProvider()
    {
        $user = new User();
        $otherUser = 'anon.';

        return [
            [
                'applications' => ['default'],
                'token' => $this->createToken($user),
                'expectedResult' => true
            ],
            [
                'applications' => ['test'],
                'token' => $this->createToken($user),
                'expectedResult' => false
            ],
            [
                'applications' => ['default'],
                'token' => $this->createToken($otherUser),
                'expectedResult' => false
            ],
            [
                'applications' => ['test'],
                'token' => $this->createToken($otherUser),
                'expectedResult' => false
            ],
            [
                'applications' => ['default'],
                'token' => null,
                'expectedResult' => false
            ],
            [
                'applications' => [],
                'token' => null,
                'expectedResult' => true
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCurrentApplicationProvider()
    {
        return [
            'supported user' => [
                'token' => $this->createToken(new User()),
                'expectedResult' => 'default',
            ],
            'not supported user' => [
                'token' => $this->createToken('anon.'),
                'expectedResult' => null,
            ],
            'empty token' => [
                'token' => null,
                'expectedResult' => null,
            ],
        ];
    }

    /**
     * @param array $applications
     * @return Operation
     */
    protected function createAction(array $applications)
    {
        $definition = new OperationDefinition();
        $definition->setApplications($applications);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Operation $operation */
        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $operation;
    }

    /**
     * @param UserInterface|string $user
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $expects
     * @return TokenInterface
     */
    protected function createToken($user, \PHPUnit_Framework_MockObject_Matcher_Invocation $expects = null)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($expects ?: $this->once())
            ->method('getUser')
            ->willReturn($user);

        return $token;
    }
}
