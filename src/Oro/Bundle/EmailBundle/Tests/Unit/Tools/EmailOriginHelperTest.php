<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailOriginHelperTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private Email&MockObject $emailModel;
    private EmailOwnerProvider&MockObject $emailOwnerProvider;
    private EmailOriginHelper $emailOriginHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->emailModel = $this->createMock(Email::class);
        $this->emailOwnerProvider = $this->createMock(EmailOwnerProvider::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityManager')
            ->with(\Oro\Bundle\EmailBundle\Entity\Email::class)
            ->willReturn($this->em);

        $organization = new Organization();
        $organization->setId(1);
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->emailOriginHelper = new EmailOriginHelper(
            $doctrineHelper,
            $tokenAccessor,
            $this->emailOwnerProvider,
            new EmailAddressHelper()
        );
    }

    public function testGetEmailOriginFromSecurity(): void
    {
        $email = 'test';
        $organization = null;
        $originName = InternalEmailOrigin::BAP;
        $expectedOrigin = new \stdClass();
        $owner = $this->createMock(EmailOwnerInterface::class);

        $this->emailOwnerProvider->expects(self::once())
            ->method('findEmailOwners')
            ->willReturn([$owner]);
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($expectedOrigin);
        $this->em->expects(self::once())
            ->method('getRepository')
            ->willReturn($entityRepository);

        self::assertEquals(
            $expectedOrigin,
            $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName)
        );
    }

    public function testGetEmailOriginCache(): void
    {
        $email = 'test';
        $organization = null;
        $originName = InternalEmailOrigin::BAP;
        $expectedOrigin = new \stdClass();
        $owner = $this->createMock(EmailOwnerInterface::class);

        $this->emailOwnerProvider->expects(self::exactly(2))
            ->method('findEmailOwners')
            ->willReturn([$owner]);
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(
                $expectedOrigin,
                null
            );
        $this->em->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturn($entityRepository);

        self::assertSame(
            $expectedOrigin,
            $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName)
        );

        self::assertInstanceOf(
            InternalEmailOrigin::class,
            $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName, false)
        );
    }

    public function testFindEmailOriginForUserOwnerAndWithUseEmailOriginAndOriginIsNotEmpty(): void
    {
        $origin = $this->createMock(UserEmailOrigin::class);
        $emailOwner = $this->createMock(User::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects(self::once())
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

    public function testFindEmailOriginForMailboxOwnerAndWithUseEmailOriginAndOriginIsNotEmpty(): void
    {
        $origin = $this->createMock(Mailbox::class);
        $emailOwner = $this->createMock(Mailbox::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects(self::never())
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

    public function testFindEmailOriginForUserOwnerAndWithUseEmailOriginAndOriginIsEmpty(): void
    {
        $origin = $this->createMock(InternalEmailOrigin::class);
        $emailOwner = $this->createMock(User::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects(self::once())
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

    public function testFindEmailOriginForMailboxOwnerAndWithUseEmailOriginAndOriginIsEmpty(): void
    {
        $origin = $this->createMock(Mailbox::class);
        $emailOwner = $this->createMock(Mailbox::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects(self::never())
            ->method('getOrganization');

        $emailOwner->expects(self::once())
            ->method('getOrigin')
            ->willReturn(null);

        $this->emailOriginHelper->setEmailModel($this->emailModel);
        self::assertNull(
            $this->emailOriginHelper->findEmailOrigin($emailOwner, $organization, $originName, true)
        );
    }

    public function testFindEmailOriginForUserOwnerAndWithoutUseEmailOriginAndOriginIsNotEmpty(): void
    {
        $origin = $this->createMock(UserEmailOrigin::class);
        $emailOwner = $this->createMock(User::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects(self::once())
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

    public function testFindEmailOriginForMailboxOwnerAndWithoutUseEmailOriginAndOriginIsNotEmpty(): void
    {
        $origin = $this->createMock(Mailbox::class);
        $emailOwner = $this->createMock(Mailbox::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects(self::never())
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

    public function testFindEmailOriginForUserOwnerAndWithoutUseEmailOriginAndOriginIsEmpty(): void
    {
        $origin = $this->createMock(UserEmailOrigin::class);
        $emailOwner = $this->createMock(User::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects(self::once())
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

    public function testFindEmailOriginForMailboxOwnerAndWithoutUseEmailOriginAndOriginIsEmpty(): void
    {
        $origin = $this->createMock(Mailbox::class);
        $emailOwner = $this->createMock(Mailbox::class);
        $organization = $this->createMock(OrganizationInterface::class);
        $originName = 'origin name';

        $origin->expects(self::never())
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
