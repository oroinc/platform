<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\RecentEmailRecipientsProvider;
use Oro\Bundle\UserBundle\Entity\User;

class RecentEmailRecipientsProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $securityFacade;
    protected $aclHelper;
    protected $relatedEmailsProvider;
    protected $registry;
    protected $emailOwnerProvider;
    protected $emailRecipientsHelper;

    private $emailRecipientsProvider;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->relatedEmailsProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($em));

        $this->emailOwnerProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailRecipientsHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailRecipientsProvider = new RecentEmailRecipientsProvider(
            $this->securityFacade,
            $this->relatedEmailsProvider,
            $this->aclHelper,
            $this->registry,
            $this->emailOwnerProvider,
            $this->emailRecipientsHelper
        );
    }

    public function testGetSectionShouldReturnRecentEmailsSection()
    {
        $this->assertEquals('oro.email.autocomplete.recently_used', $this->emailRecipientsProvider->getSection());
    }

    public function testGetRecipientsShouldReturnEmptyArrayIfUserIsNotLoggedIn()
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue(null));

        $args = new EmailRecipientsProviderArgs(null, '', 100);

        $this->assertEmpty($this->emailRecipientsProvider->getRecipients($args));
    }

    /**
     * @dataProvider emailProvider
     */
    public function testGetRecipientsShouldReturnRecipients(
        EmailRecipientsProviderArgs $args,
        array $senderEmails,
        array $resultEmails,
        array $expectedResult
    ) {
        $user = new User();

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $this->relatedEmailsProvider->expects($this->once())
            ->method('getEmails')
            ->with($user)
            ->will($this->returnValue($senderEmails));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($args->getLimit())
            ->will($this->returnSelf());

        $emailRecipientRepository = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $emailRecipientRepository->expects($this->once())
            ->method('getEmailsUsedInLast30DaysQb')
            ->with(array_keys($senderEmails), [], $args->getQuery())
            ->will($this->returnValue($qb));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($resultEmails));

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->will($this->returnValue($query));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailRecipient')
            ->will($this->returnValue($emailRecipientRepository));

        $this->emailRecipientsHelper->expects($this->any())
            ->method('isObjectAllowed')
            ->will($this->returnValue(true));

        $this->assertEquals($expectedResult, $this->emailRecipientsProvider->getRecipients($args));
    }

    public function emailProvider()
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, 'query', 100),
                ['sender@example.com' => 'Sender <sender@example.com>'],
                [
                    [
                        'email' => 'recent1@example.com',
                        'name'  => 'Recent1 <recent1@example.com>',
                    ],
                    [
                        'email' => 'recent2@example.com',
                        'name'  => 'Recent2 <recent2@example.com>',
                    ],
                ],
                [
                    new Recipient('recent1@example.com', 'Recent1 <recent1@example.com>'),
                    new Recipient('recent2@example.com', 'Recent2 <recent2@example.com>'),
                ]
            ],
        ];
    }
}
