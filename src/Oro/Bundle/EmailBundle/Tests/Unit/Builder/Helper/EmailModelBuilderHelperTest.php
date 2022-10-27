<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
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
    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRoutingHelper;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EmailAddressManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressManager;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EmailCacheManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailCacheManager;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject  */
    private $twig;

    /** @var EmailModelBuilderHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->emailAddressManager = $this->createMock(EmailAddressManager::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->emailCacheManager = $this->createMock(EmailCacheManager::class);
        $this->twig = $this->createMock(Environment::class);
        $mailboxManager = $this->createMock(MailboxManager::class);

        $this->helper = new EmailModelBuilderHelper(
            $this->entityRoutingHelper,
            new EmailAddressHelper(),
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

        $this->entityRoutingHelper->expects(self::never())
            ->method('getEntity');

        $this->entityNameResolver->expects(self::never())
            ->method('getName');

        $this->emailAddressManager->expects(self::never())
            ->method('getEmailAddressRepository');

        $this->helper->preciseFullEmailAddress($emailAddress);
    }

    public function testPreciseFullEmailAddressViaRoutingHelper(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected = '"Admin" <someaddress@example.com>';

        $ownerClass = User::class;
        $ownerId = 1;
        $owner = $this->createMock($ownerClass);
        $ownerName = 'Admin';

        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntity')
            ->with($ownerClass, $ownerId)
            ->willReturn($owner);

        $this->entityNameResolver->expects(self::once())
            ->method('getName')
            ->with($owner)
            ->willReturn($ownerName);

        $this->emailAddressManager->expects(self::never())
            ->method('getEmailAddressRepository');

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId);
        self::assertEquals($expected, $emailAddress);
    }

    public function testPreciseFullEmailAddressWithEmailOwnerAwareInterface(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected = '"Admin" <someaddress@example.com>';

        $ownerClass = User::class;
        $ownerId = 1;
        $owner = $this->createMock($ownerClass);
        $ownerName = 'Admin';

        $emailOwnerAwareStub = new EmailOwnerAwareStub($owner);

        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntity')
            ->with(EmailOwnerAwareStub::class, $ownerId)
            ->willReturn($emailOwnerAwareStub);

        $this->entityNameResolver->expects(self::once())
            ->method('getName')
            ->with($owner)
            ->willReturn($ownerName);

        $this->emailAddressManager->expects(self::never())
            ->method('getEmailAddressRepository');

        $this->helper->preciseFullEmailAddress($emailAddress, EmailOwnerAwareStub::class, $ownerId);
        self::assertEquals($expected, $emailAddress);
    }

    public function testPreciseFullEmailAddressViaRoutingHelperWithExcludeCurrentUser(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected = false;

        $ownerClass = User::class;
        $ownerId = 1;
        $owner = $this->createMock($ownerClass);

        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntity')
            ->with($ownerClass, $ownerId)
            ->willReturn($owner);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($owner);

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId, true);
        self::assertEquals($expected, $emailAddress);
    }

    public function testPreciseFullEmailAddressViaAddressManager(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected = '"Admin" <someaddress@example.com>';

        $ownerClass = User::class;
        $ownerId = null;
        $ownerName = 'Admin';

        $repo = $this->createMock(EntityRepository::class);

        $otherOwner = $this->createMock(User::class);

        $emailAddressObj = new EmailAddress();
        $emailAddressObj->setOwner($otherOwner);

        $repo->expects(self::once())
            ->method('findOneBy')
            ->willReturn($emailAddressObj);

        $this->emailAddressManager->expects(self::once())
            ->method('getEmailAddressRepository')
            ->with($this->entityManager)
            ->willReturn($repo);

        $this->entityNameResolver->expects(self::once())
            ->method('getName')
            ->with($otherOwner)
            ->willReturn($ownerName);

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId);
        self::assertEquals($expected, $emailAddress);
    }

    public function testPreciseFullEmailAddressViaAddressManagerWithExcludeCurrentUser(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected = false;

        $ownerClass = User::class;
        $ownerId = null;

        $repo = $this->createMock(EntityRepository::class);

        $otherOwner = $this->createMock(User::class);

        $emailAddressObj = new EmailAddress();
        $emailAddressObj->setOwner($otherOwner);

        $repo->expects(self::once())
            ->method('findOneBy')
            ->willReturn($emailAddressObj);

        $this->emailAddressManager->expects(self::once())
            ->method('getEmailAddressRepository')
            ->with($this->entityManager)
            ->willReturn($repo);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($otherOwner);

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId, true);
        self::assertEquals($expected, $emailAddress);
    }

    /**
     * @dataProvider preciseFullEmailAddressProvider
     */
    public function testPreciseFullEmailAddressWithProvider(
        string $expected,
        string $emailAddress,
        ?string  $ownerClass,
        ?int $ownerId
    ): void {
        $emailAddressRepository = $this->createMock(EntityRepository::class);
        $emailAddressRepository->expects(self::any())
            ->method('findOneBy')
            ->willReturnCallback(function ($args) {
                $emailAddress = new EmailAddress();
                $emailAddress->setEmail($args['email']);
                $emailAddress->setOwner(new TestUser($args['email'], 'FirstName', 'LastName'));

                return $emailAddress;
            });
        $this->emailAddressManager->expects(self::any())
            ->method('getEmailAddressRepository')
            ->with($this->identicalTo($this->entityManager))
            ->willReturn($emailAddressRepository);

        $this->entityNameResolver->expects(self::any())
            ->method('getName')
            ->with($this->isInstanceOf(TestUser::class))
            ->willReturnCallback(function ($obj) {
                return $obj->getFirstName() . ' ' . $obj->getLastName();
            });
        if ($ownerId) {
            $this->entityRoutingHelper->expects(self::once())
                ->method('getEntity')
                ->with($ownerClass, $ownerId)
                ->willReturn(new TestUser($emailAddress, 'OwnerFirstName', 'OwnerLastName'));
        }

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId);
        self::assertEquals($expected, $emailAddress);
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
                TestUser::class,
                null
            ],
            [
                '"OwnerFirstName OwnerLastName" <test@example.com>',
                'test@example.com',
                TestUser::class,
                123
            ],
        ];
    }

    public function testPreciseFulEmailAddressNoResult(): void
    {
        $emailAddress = 'someaddress@example.com';
        $expected = $emailAddress;

        $ownerClass = User::class;
        $ownerId = 2;

        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntity')
            ->with($ownerClass, $ownerId)
            ->willReturn(null);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->emailAddressManager->expects(self::once())
            ->method('getEmailAddressRepository')
            ->with($this->entityManager)
            ->willReturn($repo);

        $this->entityNameResolver->expects(self::never())
            ->method('getName');

        $this->helper->preciseFullEmailAddress($emailAddress, $ownerClass, $ownerId);
        self::assertEquals($emailAddress, $expected);
    }

    public function testGetUserTokenIsNull(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $result = $this->helper->getUser();
        self::assertNull($result);
    }

    /**
     * @dataProvider getUserProvider
     */
    public function testGetUser(object $user): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $result = $this->helper->getUser();
        self::assertSame($user, $result);
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

        $this->entityRoutingHelper->expects(self::once())
            ->method('resolveEntityClass')
            ->with($className)
            ->willReturn($className);

        $result = $this->helper->decodeClassName($className);
        self::assertEquals($result, $className);
    }

    public function testBuildFullEmailAddress(): void
    {
        $user = $this->createMock(User::class);
        $email = 'email';
        $format = 'format';
        $expected = '"format" <email>';

        $user->expects(self::once())
            ->method('getEmail')
            ->willReturn($email);

        $this->entityNameResolver->expects(self::once())
            ->method('getName')
            ->with($user)
            ->willReturn($format);

        $result = $this->helper->buildFullEmailAddress($user);
        self::assertEquals($expected, $result);
    }

    public function testGetEmailBodyWithException(): void
    {
        $emailEntity = new Email();

        $this->emailCacheManager->expects(self::once())
            ->method('ensureEmailBodyCached')
            ->with($emailEntity)
            ->willThrowException(new LoadEmailBodyException());

        $result = $this->helper->getEmailBody($emailEntity, null);
        self::assertNull($result);
    }

    public function testGetEmailBody(): void
    {
        $emailEntity = new Email();
        $templatePath = 'template_path';
        $body = 'body';

        $this->emailCacheManager->expects(self::once())
            ->method('ensureEmailBodyCached')
            ->with($emailEntity);

        $this->twig->expects(self::once())
            ->method('render')
            ->with($templatePath, ['email' => $emailEntity])
            ->willReturn($body);

        $result = $this->helper->getEmailBody($emailEntity, $templatePath);
        self::assertEquals($body, $result);
    }

    /**
     * @dataProvider prependWithProvider
     */
    public function testPrependWith(string $prefix, string $subject, string $result): void
    {
        self::assertEquals($result, $this->helper->prependWith($prefix, $subject));
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
