<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class EmailOriginHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var Email|\PHPUnit\Framework\MockObject\MockObject */
    private $emailModel;

    /** @var EmailOwnerProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOwnerProvider;

    /** @var EmailOriginHelper */
    private $emailOriginHelper;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->emailModel = $this->createMock(Email::class);
        $this->emailOwnerProvider = $this->createMock(EmailOwnerProvider::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityManager')
            ->with('OroEmailBundle:Email')
            ->willReturn($this->em);

        $organization = new Organization();
        $organization->setId(1);
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->emailOriginHelper = new EmailOriginHelper(
            $doctrineHelper,
            $tokenAccessor,
            $this->emailOwnerProvider,
            new EmailAddressHelper()
        );
    }

    public function testGetEmailOriginFromSecurity()
    {
        $email = 'test';
        $organization = null;
        $originName = InternalEmailOrigin::BAP;
        $expectedOrigin = new \stdClass();
        $owner = $this->createMock(EmailOwnerInterface::class);

        $this->emailOwnerProvider->expects($this->once())
            ->method('findEmailOwners')
            ->willReturn([$owner]);
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($expectedOrigin);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($entityRepository);

        $this->assertEquals(
            $expectedOrigin,
            $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName)
        );
    }

    public function testGetEmailOriginCache()
    {
        $email = 'test';
        $organization = null;
        $originName = InternalEmailOrigin::BAP;
        $expectedOrigin = new \stdClass();
        $owner = $this->createMock(EmailOwnerInterface::class);

        $this->emailOwnerProvider->expects($this->exactly(2))
            ->method('findEmailOwners')
            ->willReturn([$owner]);
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(
                $expectedOrigin,
                null
            );
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($entityRepository);

        $this->assertSame(
            $expectedOrigin,
            $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName)
        );

        $this->assertInstanceOf(
            InternalEmailOrigin::class,
            $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName, false)
        );
    }

    public function testFindEmailOriginForUserOwnerAndWithUseEmailOriginAndOriginIsNotEmpty()
    {
        $origin = $this->createMock(UserEmailOrigin::class);
        $emailOwner = $this->createMock(User::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $origin->expects(self::once())
            ->method('isActive')
            ->willReturn(true);
        $origin->expects(self::once())
            ->method('isSmtpConfigured')
            ->willReturn(true);

        $emailOwner->expects(self::once())
            ->method('getEmailOrigins')
            ->willReturn(new ArrayCollection([$origin]));

        $this->emailOriginHelper->setEmailModel($this->emailModel);
        self::assertInstanceOf(
            UserEmailOrigin::class,
            $this->emailOriginHelper->findEmailOrigin($emailOwner, $organization, $originName, true)
        );
    }

    public function testFindEmailOriginForMailboxOwnerAndWithUseEmailOriginAndOriginIsNotEmpty()
    {
        $origin = $this->createMock(Mailbox::class);
        $emailOwner = $this->createMock(Mailbox::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects($this->never())
            ->method('getOrganization');

        $emailOwner->expects(self::once())
            ->method('getOrigin')
            ->willReturn(new ArrayCollection([$origin]));

        $this->emailOriginHelper->setEmailModel($this->emailModel);
        self::assertInstanceOf(
            ArrayCollection::class,
            $this->emailOriginHelper->findEmailOrigin($emailOwner, $organization, $originName, true)
        );
    }

    public function testFindEmailOriginForUserOwnerAndWithUseEmailOriginAndOriginIsEmpty()
    {
        $origin = $this->createMock(InternalEmailOrigin::class);
        $emailOwner = $this->createMock(User::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $emailOwner->expects(self::exactly(2))
            ->method('getEmailOrigins')
            ->willReturn(new ArrayCollection([$origin]));

        $this->emailOriginHelper->setEmailModel($this->emailModel);
        self::assertInstanceOf(
            InternalEmailOrigin::class,
            $this->emailOriginHelper->findEmailOrigin($emailOwner, $organization, $originName, true)
        );
    }

    public function testFindEmailOriginForMailboxOwnerAndWithUseEmailOriginAndOriginIsEmpty()
    {
        $origin = $this->createMock(Mailbox::class);
        $emailOwner = $this->createMock(Mailbox::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects($this->never())
            ->method('getOrganization');

        $emailOwner->expects(self::once())
            ->method('getOrigin')
            ->willReturn(null);

        $this->emailOriginHelper->setEmailModel($this->emailModel);
        self::assertNull(
            $this->emailOriginHelper->findEmailOrigin($emailOwner, $organization, $originName, true)
        );
    }

    public function testFindEmailOriginForUserOwnerAndWithoutUseEmailOriginAndOriginIsNotEmpty()
    {
        $origin = $this->createMock(UserEmailOrigin::class);
        $emailOwner = $this->createMock(User::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $emailOwner->expects(self::once())
            ->method('getEmailOrigins')
            ->willReturn(new ArrayCollection([$origin]));
        $emailOwner->expects(self::once())
            ->method('addEmailOrigin');

        $this->emailOriginHelper->setEmailModel($this->emailModel);
        self::assertInstanceOf(
            InternalEmailOrigin::class,
            $this->emailOriginHelper->findEmailOrigin($emailOwner, $organization, $originName, false)
        );
    }

    public function testFindEmailOriginForMailboxOwnerAndWithoutUseEmailOriginAndOriginIsNotEmpty()
    {
        $origin = $this->createMock(Mailbox::class);
        $emailOwner = $this->createMock(Mailbox::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects($this->never())
            ->method('getOrganization');

        $emailOwner->expects(self::once())
            ->method('getOrigin')
            ->willReturn(new ArrayCollection([$origin]));

        $this->emailOriginHelper->setEmailModel($this->emailModel);
        self::assertInstanceOf(
            ArrayCollection::class,
            $this->emailOriginHelper->findEmailOrigin($emailOwner, $organization, $originName, false)
        );
    }

    public function testFindEmailOriginForUserOwnerAndWithoutUseEmailOriginAndOriginIsEmpty()
    {
        $origin = $this->createMock(UserEmailOrigin::class);
        $emailOwner = $this->createMock(User::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $emailOwner->expects(self::once())
            ->method('getEmailOrigins')
            ->willReturn(new ArrayCollection([$origin]));
        $emailOwner->expects(self::once())
            ->method('addEmailOrigin');

        $this->emailOriginHelper->setEmailModel($this->emailModel);
        self::assertInstanceOf(
            InternalEmailOrigin::class,
            $this->emailOriginHelper->findEmailOrigin($emailOwner, $organization, $originName, false)
        );
    }

    public function testFindEmailOriginForMailboxOwnerAndWithoutUseEmailOriginAndOriginIsEmpty()
    {
        $origin = $this->createMock(Mailbox::class);
        $emailOwner = $this->createMock(Mailbox::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects($this->never())
            ->method('getOrganization');

        $emailOwner->expects(self::once())
            ->method('getOrigin')
            ->willReturn(null);

        $this->emailOriginHelper->setEmailModel($this->emailModel);
        self::assertNull(
            $this->emailOriginHelper->findEmailOrigin($emailOwner, $organization, $originName, false)
        );
    }
}
