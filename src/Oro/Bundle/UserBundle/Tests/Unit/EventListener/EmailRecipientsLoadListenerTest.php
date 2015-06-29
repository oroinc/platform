<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\UserBundle\EventListener\EmailRecipientsLoadListener;

class EmailRecipientsLoadListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $registry;
    protected $aclHelper;
    protected $translator;
    protected $emailRecipientsHelper;

    protected $emailRecipientsLoadListener;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }));

        $this->emailRecipientsHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailRecipientsLoadListener = new EmailRecipientsLoadListener(
            $this->registry,
            $this->aclHelper,
            $this->translator,
            $this->emailRecipientsHelper
        );
    }

    public function testOnLoadShouldSetNothingIfLimitIsNotPositive()
    {
        $query = 'query';
        $limit = 0;

        $expectedResults = [];

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->onLoad($event);

        $this->assertEquals($expectedResults, $event->getResults());
    }

    public function testOnLoadShouldSetNothingIfUserRepositoryReturnsNoEmails()
    {
        $query = 'query';
        $limit = 1;

        $expectedResults = [];

        $userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->once())
            ->method('getEmails')
            ->with($this->aclHelper, [], $query, $limit)
            ->will($this->returnValue([]));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($userRepository));

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->onLoad($event);

        $this->assertEquals($expectedResults, $event->getResults());
    }

    public function testOnLoadShouldSetUserEmails()
    {
        $query = 'query';
        $limit = 1;

        $userEmails = [
            'user@example.com' => 'User <user@example.com>',
        ];

        $expectedResults = [
            [
                'text' => 'oro.user.entity_plural_label',
                'children' => [
                    [
                        'id'   => 'user@example.com',
                        'text' => 'User <user@example.com>',
                    ],
                ],
            ],
        ];

        $userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->once())
            ->method('getEmails')
            ->with($this->aclHelper, [], $query, $limit)
            ->will($this->returnValue($userEmails));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($userRepository));

        $this->emailRecipientsHelper->expects($this->once())
            ->method('createResultFromEmails')
            ->with($userEmails)
            ->will($this->returnValue($expectedResults[0]['children']));

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->onLoad($event);

        $this->assertEquals($expectedResults, $event->getResults());
    }
}
