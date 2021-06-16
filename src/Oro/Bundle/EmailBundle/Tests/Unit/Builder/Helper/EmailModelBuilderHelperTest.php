<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder\Helper;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailModelBuilderHelperTest extends \PHPUnit\Framework\TestCase
{
    protected EmailModelBuilderHelper $helper;

    protected EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject $entityRoutingHelper;

    protected EmailAddressHelper|\PHPUnit\Framework\MockObject\MockObject $emailAddressHelper;

    protected EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject $entityNameResolver;

    protected TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor;

    protected EmailAddressManager|\PHPUnit\Framework\MockObject\MockObject $emailAddressManager;

    protected EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected EmailCacheManager|\PHPUnit\Framework\MockObject\MockObject $emailCacheManager;

    protected Environment|\PHPUnit\Framework\MockObject\MockObject $twig;

    protected function setUp(): void
    {
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);

        $this->emailAddressHelper = new EmailAddressHelper();

        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->emailAddressManager = $this->createMock(EmailAddressManager::class);

        $this->entityManager = $this->createMock(EntityManager::class);

        $this->emailCacheManager = $this->createMock(EmailCacheManager::class);

        $this->twig = $this->createMock(Environment::class);

        $mailboxManager = $this->createMock(MailboxManager::class);

        $this->helper = new EmailModelBuilderHelper(
            $this->entityRoutingHelper,
            $this->emailAddressHelper,
            $this->entityNameResolver,
            $this->tokenAccessor,
            $this->emailAddressManager,
            $this->entityManager,
            $this->emailCacheManager,
            $this->twig,
            $mailboxManager
        );
    }

    public function testPreciseFullEmailAddressIsFullQualifiedName(): void
    {
        $emailAddress = '"Admin" <someaddress@example.com>';

        $this->entityRoutingHelper->expects($this->never())
            ->method('getEntity');

        $this->entityNameResolver->expects($this->never())
            ->method('getName');

        $this->emailAddressManager->expects($this->never())
            ->method('getEmailAddressRepository');

        $this->helper->preciseFullEmailAddress($emailAddress, null, null);
    }

    public function testPreciseFullEmailAddressViaRoutingHelper(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected     = '"Admin" <someaddress@example.com>';

        $ownerClass = User::class;
        $ownerId    = 1;
        $owner      = $this->createMock($ownerClass);
        $ownerName  = 'Admin';

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntity')
            ->with($ownerClass, $ownerId)
            ->willReturn($owner);

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($owner)
            ->willReturn($ownerName);

        $this->emailAddressManager->expects($this->never())
            ->method('getEmailAddressRepository');

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId);
        $this->assertEquals($expected, $emailAddress);
    }

    public function testPreciseFullEmailAddressWithEmailOwnerAwareInterface(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected     = '"Admin" <someaddress@example.com>';

        $ownerClass = User::class;
        $ownerId    = 1;
        /** @var EmailOwnerInterface $owner */
        $owner      = $this->createMock($ownerClass);
        $ownerName  = 'Admin';

        $emailOwnerAwareStub = new EmailOwnerAwareStub($owner);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntity')
            ->with(EmailOwnerAwareStub::class, $ownerId)
            ->willReturn($emailOwnerAwareStub);

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($owner)
            ->willReturn($ownerName);

        $this->emailAddressManager->expects($this->never())
            ->method('getEmailAddressRepository');

        $this->helper->preciseFullEmailAddress($emailAddress, EmailOwnerAwareStub::class, $ownerId);
        $this->assertEquals($expected, $emailAddress);
    }

    public function testPreciseFullEmailAddressViaRoutingHelperWithExcludeCurrentUser(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected     = false;

        $ownerClass = User::class;
        $ownerId    = 1;
        $owner      = $this->createMock($ownerClass);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntity')
            ->with($ownerClass, $ownerId)
            ->willReturn($owner);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($owner);

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId, true);
        $this->assertEquals($expected, $emailAddress);
    }

    public function testPreciseFullEmailAddressViaAddressManager(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected     = '"Admin" <someaddress@example.com>';

        $ownerClass = User::class;
        $ownerId    = null;
        $ownerName  = 'Admin';

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $otherOwner = $this->createMock(User::class);

        $emailAddressObj = new EmailAddress();
        $emailAddressObj->setOwner($otherOwner);

        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($emailAddressObj);

        $this->emailAddressManager->expects($this->once())
            ->method('getEmailAddressRepository')
            ->with($this->entityManager)
            ->willReturn($repo);

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($otherOwner)
            ->willReturn($ownerName);

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId);
        $this->assertEquals($expected, $emailAddress);
    }

    public function testPreciseFullEmailAddressViaAddressManagerWithExcludeCurrentUser(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected     = false;

        $ownerClass = User::class;
        $ownerId    = null;

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $otherOwner = $this->createMock(User::class);

        $emailAddressObj = new EmailAddress();
        $emailAddressObj->setOwner($otherOwner);

        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($emailAddressObj);

        $this->emailAddressManager->expects($this->once())
            ->method('getEmailAddressRepository')
            ->with($this->entityManager)
            ->willReturn($repo);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($otherOwner);

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId, true);
        $this->assertEquals($expected, $emailAddress);
    }

    /**
     * @dataProvider preciseFullEmailAddressProvider
     */
    public function testPreciseFullEmailAddressWithProvider($expected, $emailAddress, $ownerClass, $ownerId): void
    {
        $emailAddressRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $emailAddressRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturnCallback(
                function ($args) {
                    $emailAddress = new EmailAddress();
                    $emailAddress->setEmail($args['email']);
                    $emailAddress->setOwner(new TestUser($args['email'], 'FirstName', 'LastName'));

                    return $emailAddress;
                }
            );
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressRepository')
            ->with($this->identicalTo($this->entityManager))
            ->willReturn($emailAddressRepository);

        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser'))
            ->willReturnCallback(
                function ($obj) {
                    return $obj->getFirstName() . ' ' . $obj->getLastName();
                }
            );
        if ($ownerId) {
            $this->entityRoutingHelper->expects($this->once())
                ->method('getEntity')
                ->with($ownerClass, $ownerId)
                ->willReturn(new TestUser($emailAddress, 'OwnerFirstName', 'OwnerLastName'));
        }

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId);
        $this->assertEquals($expected, $emailAddress);
    }

    public function preciseFullEmailAddressProvider(): array
    {
        return [
            [
                '"FirstName LastName" <test@example.com>',
                'test@example.com',
                null,
                null
            ],
            [
                '"FirstName LastName" <test@example.com>',
                'test@example.com',
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser',
                null
            ],
            [
                '"OwnerFirstName OwnerLastName" <test@example.com>',
                'test@example.com',
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser',
                123
            ],
        ];
    }

    public function testPreciseFulEmailAddressNoResult(): void
    {
        $emailAddress = $expected = 'someaddress@example.com';

        $ownerClass = User::class;
        $ownerId    = 2;

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntity')
            ->with($ownerClass, $ownerId)
            ->willReturn(null);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->emailAddressManager->expects($this->once())
            ->method('getEmailAddressRepository')
            ->with($this->entityManager)
            ->willReturn($repo);

        $this->entityNameResolver->expects($this->never())
            ->method('getName');

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId);
        $this->assertEquals($emailAddress, $expected);
    }

    public function testGetUserTokenIsNull(): void
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $result = $this->helper->getUser();
        $this->assertNull($result);
    }

    /**
     * @param object $user
     * @dataProvider getUserProvider
     */
    public function testGetUser($user): void
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $result = $this->helper->getUser();
        $this->assertSame($user, $result);
    }

    public function getUserProvider(): array
    {
        return [
            [new User()],
        ];
    }

    public function testDecodeClassName(): void
    {
        $className = 'Class';

        $this->entityRoutingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($className)
            ->willReturn($className);

        $result = $this->helper->decodeClassName($className);
        $this->assertEquals($result, $className);
    }

    public function testBuildFullEmailAddress(): void
    {
        $user = $this->createMock(User::class);
        $email = 'email';
        $format = 'format';
        $expected = '"format" <email>';

        $user->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($user)
            ->willReturn($format);

        $result = $this->helper->buildFullEmailAddress($user);
        $this->assertEquals($expected, $result);
    }

    public function testGetEmailBodyWithException(): void
    {
        $exception = $this->createMock('Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException');
        $emailEntity = new Email();

        $this->emailCacheManager->expects($this->once())
            ->method('ensureEmailBodyCached')
            ->with($emailEntity)
            ->willThrowException($exception);

        $result = $this->helper->getEmailBody($emailEntity, null);
        $this->assertNull($result);
    }

    public function testGetEmailBody(): void
    {
        $emailEntity = new Email();
        $templatePath = 'template_path';
        $body = 'body';

        $this->emailCacheManager->expects($this->once())
            ->method('ensureEmailBodyCached')
            ->with($emailEntity);

        $this->twig->expects($this->once())
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
    public function testPrependWith(string $prefix, string $subject, string $result): void
    {
        $this->assertEquals($result, $this->helper->prependWith($prefix, $subject));
    }

    public function prependWithProvider(): array
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
