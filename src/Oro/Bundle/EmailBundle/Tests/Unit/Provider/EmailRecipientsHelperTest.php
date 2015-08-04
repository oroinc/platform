<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;

class EmailRecipientsHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $aclHelper;
    protected $nameFormatter;

    protected $emailRecipientsHelper;

    public function setUp()
    {
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailRecipientsHelper = new EmailRecipientsHelper($this->aclHelper, $this->nameFormatter);
    }

    /**
     * @dataProvider getRecipientsDataProvider
     */
    public function testGetRecipients(EmailRecipientsProviderArgs $args, array $resultEmails)
    {
        $this->nameFormatter->expects($this->once())
            ->method('getFormattedNameDQL')
            ->with('u', 'Oro\Bundle\UserBundle\Entity\User')
            ->will($this->returnValue('u.name'));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->will($this->returnSelf());

        $userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->once())
            ->method('getPrimaryEmailsQb')
            ->will($this->returnValue($qb));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractclass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($resultEmails));

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->will($this->returnValue($query));

        $this->emailRecipientsHelper->getRecipients($args, $userRepository, 'u', 'Oro\Bundle\UserBundle\Entity\User');
    }

    public function getRecipientsDataProvider()
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, null, 1),
                [
                    [
                        'name'  => 'Recipient <recipient@example.com>',
                        'email' => 'recipient@example.com',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider filterRecipientsDataProvider
     */
    public function testFilterRecipients(EmailRecipientsProviderArgs $args, array $recipients, array $expectedResult)
    {
        $this->assertEquals($expectedResult, EmailRecipientsHelper::filterRecipients($args, $recipients));
    }

    public function filterRecipientsDataProvider()
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, 're', 100),
                [
                    'recipient@example.com' => 'Recipient <recipient@example.com>',
                ],
                [
                    'recipient@example.com' => 'Recipient <recipient@example.com>',
                ],
            ],
            [
                new EmailRecipientsProviderArgs(null, 'res', 100),
                [
                    'recipient@example.com' => 'Recipient <recipient@example.com>',
                ],
                [],
            ],
            [
                new EmailRecipientsProviderArgs(null, 're', 100, ['recipient@example.com']),
                [
                    'recipient@example.com' => 'Recipient <recipient@example.com>',
                    'recipient2@example.com' => 'Recipient2 <recipient2@example.com>',
                ],
                [
                    'recipient2@example.com' => 'Recipient2 <recipient2@example.com>',
                ],
            ],
        ];
    }
}
