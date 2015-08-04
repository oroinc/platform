<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\UserBundle\Provider\EmailRecipientsProvider;

class EmailRecipientsProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $registry;
    protected $emailRecipientsHelper;

    protected $emailRecipientsProvider;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailRecipientsHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailRecipientsProvider = new EmailRecipientsProvider(
            $this->registry,
            $this->emailRecipientsHelper
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetRecipients(EmailRecipientsProviderArgs $args, array $recipients)
    {
        $userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($userRepository));

        $this->emailRecipientsHelper->expects($this->once())
            ->method('getRecipients')
            ->with($args, $userRepository, 'u', 'Oro\Bundle\UserBundle\Entity\User')
            ->will($this->returnValue($recipients));

        $this->assertEquals($recipients, $this->emailRecipientsProvider->getRecipients($args));
    }

    public function dataProvider()
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, null, 1),
                [
                    'recipient@example.com'  => 'Recipient <recipient@example.com>',
                ],
            ],
        ];
    }
}
