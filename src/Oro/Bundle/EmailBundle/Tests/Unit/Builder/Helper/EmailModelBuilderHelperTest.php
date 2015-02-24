<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder\Helper;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Symfony\Component\Templating\EngineInterface;

use Symfony\Component\Security\Core\SecurityContext;

class EmailModelBuilderHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailModelBuilderHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var EntityRoutingHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRoutingHelper;

    /**
     * @var EmailAddressHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailAddressHelper;

    /**
     * @var NameFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nameFormatter;

    /**
     * @var SecurityContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * @var EmailAddressManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailAddressManager;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var EmailCacheManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailCacheManager;

    /**
     * @var EngineInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $templating;

    protected function setUp()
    {
        $this->entityRoutingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAddressHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tools\EmailAddressHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAddressManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailCacheManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Cache\EmailCacheManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->templating = $this->getMock('Symfony\Component\Templating\EngineInterface');

        $this->helper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper')
            ->setConstructorArgs([
                $this->entityRoutingHelper,
                $this->emailAddressHelper,
                $this->nameFormatter,
                $this->securityContext,
                $this->emailAddressManager,
                $this->entityManager,
                $this->emailCacheManager,
                $this->templating,
            ])
            ->setMethods([
                'isFullQualifiedUser',
                'preciseViaRoutingHelper',
                'preciseViaAddressManager',
            ])
            ->getMock();
    }

    /**
     * @param boolean     $isFullEmailAddress
     * @param string|null $ownerClass
     * @param int|null    $ownerId
     * @param int         $preciseViaRoutingHelperCalls
     * @param int         $preciseViaAddressManagerCalls
     *
     * @dataProvider preciseFullEmailAddressProvider
     */
    public function testPreciseFullEmailAddress(
        $isFullEmailAddress,
        $ownerClass,
        $ownerId,
        $preciseViaRoutingHelperCalls,
        $preciseViaAddressManagerCalls
    ) {
        $this->emailAddressHelper->expects($this->once())
            ->method('isFullEmailAddress')
            ->willReturn($isFullEmailAddress);

        $this->helper->expects($this->exactly($preciseViaRoutingHelperCalls))
            ->method('preciseViaRoutingHelper');

        $this->helper->expects($this->exactly($preciseViaAddressManagerCalls))
            ->method('preciseViaAddressManager');

        $this->helper->preciseFullEmailAddress('someaddress@example.com', $ownerClass, $ownerId);
    }

    public function preciseFullEmailAddressProvider()
    {
        return [
            [
                'isFullEmailAddress'            => true,
                'ownerClass'                    => 'Class',
                'ownerId'                       => 1,
                'preciseViaRoutingHelperCalls'  => 0,
                'preciseViaAddressManagerCalls' => 0,
            ],
            [
                'isFullEmailAddress'            => false,
                'ownerClass'                    => 'Class',
                'ownerId'                       => 1,
                'preciseViaRoutingHelperCalls'  => 1,
                'preciseViaAddressManagerCalls' => 1,
            ],
            [
                'isFullEmailAddress'            => false,
                'ownerClass'                    => null,
                'ownerId'                       => 1,
                'preciseViaRoutingHelperCalls'  => 0,
                'preciseViaAddressManagerCalls' => 1,
            ],
            [
                'isFullEmailAddress'            => false,
                'ownerClass'                    => 'Class',
                'ownerId'                       => null,
                'preciseViaRoutingHelperCalls'  => 0,
                'preciseViaAddressManagerCalls' => 1,
            ],
        ];
    }

    public function testGetUserTokenIsNull()
    {
        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $result = $this->helper->getUser();
        $this->assertNull($result);
    }

    /**
     * @param boolean $isFullQualifiedUser
     *
     * @dataProvider getUserProvider
     */
    public function testGetUserTokenIsNotNull($isFullQualifiedUser)
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->helper->expects($this->once())
            ->method('isFullQualifiedUser')
            ->willReturn($isFullQualifiedUser);

        $result = $this->helper->getUser();
        if ($isFullQualifiedUser) {
            $this->assertEquals($user, $result);
        } else {
            $this->assertNull($result);
        }
    }

    public function getUserProvider()
    {
        return [
            ['isFullQualifiedUser' => false,],
            ['isFullQualifiedUser' => true,]
        ];
    }

    public function testDecodeClassName()
    {
        $className = 'Class';

        $this->entityRoutingHelper->expects($this->once())
            ->method('decodeClassName')
            ->with($className)
            ->willReturn($className);

        $result = $this->helper->decodeClassName($className);
        $this->assertEquals($result, $className);
    }

    public function testBuildFullEmailAddress()
    {
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $email = 'email';
        $format = 'format';
        $expected = 'result';

        $user->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->with($user)
            ->willReturn($format);

        $this->emailAddressHelper->expects($this->once())
            ->method('buildFullEmailAddress')
            ->with($email, $format)
            ->willReturn($expected);

        $result = $this->helper->buildFullEmailAddress($user);
        $this->assertEquals($expected, $result);
    }

    public function testGetEmailBodyWithException()
    {
        $exception = $this->getMock('Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException');
        $emailEntity = new Email();

        $this->emailCacheManager->expects($this->once())
            ->method('ensureEmailBodyCached')
            ->with($emailEntity)
            ->willThrowException($exception);

        $result = $this->helper->getEmailBody($emailEntity, null);
        $this->assertNull($result);
    }

    public function testGetEmailBody()
    {
        $emailEntity = new Email();
        $templatePath = 'template_path';
        $body = 'body';

        $this->emailCacheManager->expects($this->once())
            ->method('ensureEmailBodyCached')
            ->with($emailEntity);

        $this->templating->expects($this->once())
            ->method('render')
            ->with($templatePath, ['email' => $emailEntity])
            ->willReturn($body);

        $result = $this->helper->getEmailBody($emailEntity, $templatePath);
        $this->assertEquals($body, $result);
    }

    /**
     * @param string $prefix
     * @param string $subject
     * @param string $result
     *
     * @dataProvider prependWithProvider
     */
    public function testPrependWith($prefix, $subject, $result)
    {
        $this->assertEquals($result, $this->helper->prependWith($prefix, $subject));
    }

    public function prependWithProvider()
    {
        return [
            [
                'prefix'  => 'Re: ',
                'subject' => 'Subject',
                'result'  => 'Re: Subject',
            ],
            [
                'prefix'  => 'Fwd: ',
                'subject' => 'Subject',
                'result'  => 'Fwd: Subject',
            ],
            [
                'prefix'  => 'Re: ',
                'subject' => 'Re: Subject',
                'result'  => 'Re: Subject',
            ],
            [
                'prefix'  => 'Fwd: ',
                'subject' => 'Fwd: Subject',
                'result'  => 'Fwd: Subject',
            ],
            [
                'prefix'  => '',
                'subject' => 'Subject',
                'result'  => 'Subject',
            ],
        ];
    }
}
