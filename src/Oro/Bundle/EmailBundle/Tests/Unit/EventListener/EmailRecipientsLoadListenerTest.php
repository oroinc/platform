<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\EmailBundle\EventListener\EmailRecipientsLoadListener;
use Oro\Bundle\UserBundle\Entity\User;

class EmailRecipientsLoadListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $securityFacade;
    protected $aclHelper;
    protected $relatedEmailsProvider;
    protected $registry;
    protected $translator;

    protected $emailRecipientsLoadListener;

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

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }));

        $this->emailRecipientsLoadListener = new EmailRecipientsLoadListener(
            $this->securityFacade,
            $this->aclHelper,
            $this->relatedEmailsProvider,
            $this->registry,
            $this->translator
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

    public function testOnLoadShouldSetNothingIfNoUserIsLoggedIn()
    {
        $query = 'query';
        $limit = 1;

        $expectedResults = [];

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue(null));

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->onLoad($event);
        
        $this->assertEquals($expectedResults, $event->getResults());
    }

    public function testOnLoadShouldSetNothingIfThereAreNoRecentEmails()
    {
        $query = 'query';
        $limit = 1;

        $userEmailAddresses = [
            'user@example.com',
        ];

        $recentEmailAddresses = [];

        $expectedResults = [];

        $user = new User();
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $this->relatedEmailsProvider->expects($this->once())
            ->method('getEmails')
            ->with($user)
            ->will($this->returnValue($userEmailAddresses));

        $emailRecipientRepository =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository')
                ->disableOriginalConstructor()
                ->getMock();
        $emailRecipientRepository->expects($this->once())
            ->method('getEmailsUsedInLast30Days')
            ->with($this->aclHelper, $userEmailAddresses, [], $query, $limit)
            ->will($this->returnValue($recentEmailAddresses));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailRecipient')
            ->will($this->returnValue($emailRecipientRepository));

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->onLoad($event);

        $this->assertEquals($expectedResults, $event->getResults());
    }

    public function testOnLoadShouldSetRecentEmails()
    {
        $query = 'query';
        $limit = 1;

        $userEmailAddresses = [
            'user@example.com',
        ];

        $recentEmailAddresses = [
            'recent@example.com' => 'Recent <recent@example.com>',
        ];

        $expectedResults = [
            [
                'text'     => 'oro.email.autocomplete.recently_used',
                'children' => [
                    [
                        'id'   => 'recent@example.com',
                        'text' => 'Recent <recent@example.com>',
                    ],
                ],
            ],
        ];

        $user = new User();
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $this->relatedEmailsProvider->expects($this->once())
            ->method('getEmails')
            ->with($user)
            ->will($this->returnValue($userEmailAddresses));

        $emailRecipientRepository =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository')
                ->disableOriginalConstructor()
                ->getMock();
        $emailRecipientRepository->expects($this->once())
            ->method('getEmailsUsedInLast30Days')
            ->with($this->aclHelper, $userEmailAddresses, [], $query, $limit)
            ->will($this->returnValue($recentEmailAddresses));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailRecipient')
            ->will($this->returnValue($emailRecipientRepository));

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->onLoad($event);

        $this->assertEquals($expectedResults, $event->getResults());
    }
}
