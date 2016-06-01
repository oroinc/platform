<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class EmailOriginHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailOriginHelper */
    protected $emailOriginHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailModel;

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

        $this->emailOriginHelper = new EmailOriginHelper($this->doctrineHelper);
    }

    /**
     * @dataProvider findEmailOriginDataProvider
     *
     * @param bool                                               $expected
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $mailModelExpects
     * @param string                                             $emailOwner
     * @param bool                                               $enableUseUserEmailOrigin
     * @param bool                                               $isOriginsNotEmpty
     * @param \PHPUnit_Framework_MockObject_MockObject           $origin
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $emailOriginsTimes
     */
    public function testFindEmailOrigin(
        $expected,
        $mailModelExpects,
        $emailOwner,
        $enableUseUserEmailOrigin,
        $isOriginsNotEmpty,
        \PHPUnit_Framework_MockObject_MockObject $origin,
        $emailOriginsTimes,
        $exactly
    ) {
        $organization  = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');
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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEmailOwnerMailBoxMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Mailbox')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getUserEmailOriginMock()
    {
        return $this->getMockBuilder('Oro\Bundle\ImapBundle\Entity\UserEmailOrigin')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getInternalEmailOriginMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin')
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
