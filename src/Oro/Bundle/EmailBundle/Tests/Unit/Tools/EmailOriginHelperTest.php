<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class EmailOriginHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailOriginHelper */
    protected $emailOriginHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $emailModel;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $emailOwnerProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var  EmailAddressHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailAddressHelper;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManager')
            ->with('OroEmailBundle:Email')
            ->will(self::returnValue($this->em));

        $this->emailModel = $this->getMockBuilder('Oro\Bundle\EmailBundle\Form\Model\Email')
            ->setMethods(['getCampaignOwner'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailOwnerProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($this->getTestOrganization()));

        $this->emailAddressHelper = new EmailAddressHelper();

        $this->emailOriginHelper = new EmailOriginHelper(
            $this->doctrineHelper,
            $this->tokenAccessor,
            $this->emailOwnerProvider,
            $this->emailAddressHelper
        );
    }

    public function testGetEmailOriginFromSecurity()
    {
        $email = 'test';
        $organization = null;
        $originName = InternalEmailOrigin::BAP;
        $enableUseUserEmailOrigin = true;
        $expectedOrigin = new \stdClass();
        $owner = new \stdClass();

        $this->emailOwnerProvider->expects($this->once())
            ->method('findEmailOwners')
            ->willReturn($owner);
        $entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($expectedOrigin);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($entityRepository);

        $origin =
            $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName, $enableUseUserEmailOrigin);

        $this->assertEquals($expectedOrigin, $origin);
    }

    public function testGetEmailOriginCache()
    {
        $email = 'test';
        $organization = null;
        $originName = InternalEmailOrigin::BAP;
        $enableUseUserEmailOrigin = true;
        $expectedOrigin = new \stdClass();
        $owner = new \stdClass();

        $this->emailOwnerProvider->expects($this->exactly(2))
            ->method('findEmailOwners')
            ->willReturn($owner);
        $entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepository->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($expectedOrigin);
        $entityRepository->expects($this->at(1))
            ->method('findOneBy')
            ->willReturn(null);
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($entityRepository);

        $origin =
            $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName, $enableUseUserEmailOrigin);

        $this->assertEquals($expectedOrigin, $origin);

        $origin = $this->emailOriginHelper->getEmailOrigin($email, $organization, $originName, false);

        $this->assertNull($origin);
    }

    /**
     * @dataProvider findEmailOriginDataProvider
     *
     * @param bool                                               $expected
     * @param \PHPUnit\Framework\MockObject\Matcher\InvokedCount $mailModelExpects
     * @param string                                             $emailOwner
     * @param bool                                               $enableUseUserEmailOrigin
     * @param bool                                               $isOriginsNotEmpty
     * @param \PHPUnit\Framework\MockObject\MockObject           $origin
     * @param \PHPUnit\Framework\MockObject\Matcher\InvokedCount $emailOriginsTimes
     */
    public function testFindEmailOrigin(
        $expected,
        $mailModelExpects,
        $emailOwner,
        $enableUseUserEmailOrigin,
        $isOriginsNotEmpty,
        \PHPUnit\Framework\MockObject\MockObject $origin,
        $emailOriginsTimes,
        $exactly
    ) {
        $organization  = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');
        $collection    = new ArrayCollection([$origin]);
        $originName    = 'origin name';
        $campaignOwner = null;

        $origin->expects($this->exactly($exactly))->method('getOrganization')->willReturn($organization);

        if (true === $enableUseUserEmailOrigin && true === $isOriginsNotEmpty) {
            if (!($origin instanceof \Oro\Bundle\EmailBundle\Entity\Mailbox)) {
                $origin->expects(self::once())->method('isActive')->willReturn($isOriginsNotEmpty);
                $origin->expects(self::once())->method('isSmtpConfigured')->willReturn($isOriginsNotEmpty);
            }
        }

        if ('user' === $emailOwner) {
            $emailOwner = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
                ->setMethods(['getEmailOrigins', 'addEmailOrigin'])
                ->disableOriginalConstructor()
                ->getMock();
            $emailOwner
                ->expects($emailOriginsTimes)
                ->method('getEmailOrigins')
                ->willReturn($collection);

            if (false === $enableUseUserEmailOrigin) {
                $emailOwner->expects(self::once())->method('addEmailOrigin');
            }
        } elseif ('emailBox' === $emailOwner) {
            $emailOwner = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Mailbox')
                ->setMethods(['getOrigin'])
                ->disableOriginalConstructor()
                ->getMock();

            if (true === $isOriginsNotEmpty) {
                $emailOwner
                    ->expects($emailOriginsTimes)
                    ->method('getOrigin')
                    ->willReturn($collection);
            } else {
                $emailOwner
                    ->expects($emailOriginsTimes)
                    ->method('getOrigin')
                    ->willReturn(null);

                $campaignOwner = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
                    ->setMethods(['getEmailOrigins', 'addEmailOrigin'])
                    ->disableOriginalConstructor()
                    ->getMock();

                $campaignOwner
                    ->expects(self::any())
                    ->method('getEmailOrigins')
                    ->willReturn($collection);
            }
        }

        $this->emailOriginHelper->setEmailModel($this->emailModel);

        $origin = $this->emailOriginHelper
            ->findEmailOrigin($emailOwner, $organization, $originName, $enableUseUserEmailOrigin);

        if (!$expected) {
            self::assertNull($origin);
        } else {
            self::assertInstanceOf($expected, $origin);
        }
    }

    /**
     * @return array
     */
    public function findEmailOriginDataProvider()
    {
        return [
            '1. EmailOwner as User with enableUseUserEmailOrigin and origin is not empty'            => [
                'expected'                 => 'Oro\Bundle\ImapBundle\Entity\UserEmailOrigin',
                'mailModelExpects'         => self::never(),
                'emailOwner'               => 'user',
                'enableUseUserEmailOrigin' => true,
                'isUserOriginsNotEmpty'    => true,
                'origin'                   => $this->getUserEmailOriginMock(),
                'emailOriginsTimes'        => self::once(),
                'exactly'                  => 1
            ],
            '2. EmailOwner as User with enableUseUserEmailOrigin and origin is empty'                => [
                'expected'                 => 'Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin',
                'mailModelExpects'         => self::never(),
                'emailOwner'               => 'user',
                'enableUseUserEmailOrigin' => true,
                'isUserOriginsNotEmpty'    => false,
                'origin'                   => $this->getInternalEmailOriginMock(),
                'emailOriginsTimes'        => self::exactly(2),
                'exactly'                  => 1
            ],
            '3. EmailOwner as User without enableUseUserEmailOrigin and origin is not empty'         => [
                'expected'                 => 'Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin',
                'mailModelExpects'         => self::never(),
                'emailOwner'               => 'user',
                'enableUseUserEmailOrigin' => false,
                'isUserOriginsNotEmpty'    => true,
                'origin'                   => $this->getUserEmailOriginMock(),
                'emailOriginsTimes'        => self::once(),
                'exactly'                  => 1
            ],
            '4. EmailOwner as User without enableUseUserEmailOrigin and origin is empty'             => [
                'expected'                 => 'Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin',
                'mailModelExpects'         => self::never(),
                'emailOwner'               => 'user',
                'enableUseUserEmailOrigin' => false,
                'isUserOriginsNotEmpty'    => false,
                'origin'                   => $this->getUserEmailOriginMock(),
                'emailOriginsTimes'        => self::once(),
                'exactly'                  => 1
            ],
            '5. EmailOwner as Mailbox origin is not empty and enableUseUserEmailOrigin is empty'     => [
                'expected'                 => 'Doctrine\Common\Collections\ArrayCollection',
                'mailModelExpects'         => self::never(),
                'emailOwner'               => 'emailBox',
                'enableUseUserEmailOrigin' => false,
                'isUserOriginsNotEmpty'    => true,
                'origin'                   => $this->getEmailOwnerMailBoxMock(),
                'emailOriginsTimes'        => self::once(),
                'exactly'                  => 0
            ],
            '6. EmailOwner as Mailbox origin is not empty and enableUseUserEmailOrigin is not empty' => [
                'expected'                 => 'Doctrine\Common\Collections\ArrayCollection',
                'mailModelExpects'         => self::never(),
                'emailOwner'               => 'emailBox',
                'enableUseUserEmailOrigin' => true,
                'isUserOriginsNotEmpty'    => true,
                'origin'                   => $this->getEmailOwnerMailBoxMock(),
                'emailOriginsTimes'        => self::once(),
                'exactly'                  => 0
            ],
            '7. EmailOwner as Mailbox origin is empty and enableUseUserEmailOrigin is empty'         => [
                'expected'                 => null,
                'mailModelExpects'         => self::once(),
                'emailOwner'               => 'emailBox',
                'enableUseUserEmailOrigin' => false,
                'isUserOriginsNotEmpty'    => false,
                'origin'                   => $this->getEmailOwnerMailBoxMock(),
                'emailOriginsTimes'        => self::once(),
                'exactly'                  => 1
            ],
            '8. EmailOwner as Mailbox origin is not empty and enableUseUserEmailOrigin is empty'     => [
                'expected'                 => null,
                'mailModelExpects'         => self::once(),
                'emailOwner'               => 'emailBox',
                'enableUseUserEmailOrigin' => true,
                'isUserOriginsNotEmpty'    => false,
                'origin'                   => $this->getEmailOwnerMailBoxMock(),
                'emailOriginsTimes'        => self::once(),
                'exactly'                  => 1
            ],
        ];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEmailOwnerMailBoxMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Mailbox')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getUserEmailOriginMock()
    {
        return $this->getMockBuilder('Oro\Bundle\ImapBundle\Entity\UserEmailOrigin')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getInternalEmailOriginMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    protected function getTestOrganization()
    {
        $organization = new Organization();
        $organization->setId(1);

        return $organization;
    }

    protected function getTestOrigin()
    {
        $outboxFolder = new EmailFolder();
        $outboxFolder
            ->setType(FolderType::SENT)
            ->setName(FolderType::SENT)
            ->setFullName(FolderType::SENT);

        $origin = new InternalEmailOrigin();
        $origin
            ->setName('BAP_User_1')
            ->addFolder($outboxFolder)
            ->setOwner($this->getTestUser())
            ->setOrganization($this->getTestOrganization());

        return $origin;
    }
}
