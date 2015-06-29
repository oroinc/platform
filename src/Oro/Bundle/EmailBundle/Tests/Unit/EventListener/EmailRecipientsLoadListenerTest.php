<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Entity\Email;
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
    protected $emailRecipientsHelper;

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

        $this->emailRecipientsHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailRecipientsLoadListener = new EmailRecipientsLoadListener(
            $this->securityFacade,
            $this->aclHelper,
            $this->relatedEmailsProvider,
            $this->registry,
            $this->translator,
            $this->emailRecipientsHelper
        );
    }

    public function testLoadRecentEmailsShouldSetNothingIfLimitIsNotPositive()
    {
        $query = 'query';
        $limit = 0;

        $expectedResults = [];

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->loadRecentEmails($event);
        
        $this->assertEquals($expectedResults, $event->getResults());
    }

    public function testLoadRecentEmailsShouldSetNothingIfNoUserIsLoggedIn()
    {
        $query = 'query';
        $limit = 1;

        $expectedResults = [];

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue(null));

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->loadRecentEmails($event);
        
        $this->assertEquals($expectedResults, $event->getResults());
    }

    public function testLoadRecentEmailsShouldSetNothingIfThereAreNoRecentEmails()
    {
        $query = 'query';
        $limit = 1;

        $userEmailAddresses = [
            'user@example.com' => 'User <user@example.com>',
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
            ->with($this->aclHelper, array_keys($userEmailAddresses), [], $query, $limit)
            ->will($this->returnValue($recentEmailAddresses));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailRecipient')
            ->will($this->returnValue($emailRecipientRepository));

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->loadRecentEmails($event);

        $this->assertEquals($expectedResults, $event->getResults());
    }

    public function testLoadRecentEmailsShouldSetRecentEmails()
    {
        $query = 'query';
        $limit = 1;

        $userEmailAddresses = [
            'user@example.com' => 'User <user@example.com>',
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
            ->with($this->aclHelper, array_keys($userEmailAddresses), [], $query, $limit)
            ->will($this->returnValue($recentEmailAddresses));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailRecipient')
            ->will($this->returnValue($emailRecipientRepository));

        $this->emailRecipientsHelper->expects($this->once())
            ->method('createResultFromEmails')
            ->with($recentEmailAddresses)
            ->will($this->returnValue($expectedResults[0]['children']));

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->loadRecentEmails($event);

        $this->assertEquals($expectedResults, $event->getResults());
    }

    public function testLoadContextEmailsShouldNotAddStuffInContextIfLimitIsNotPositive()
    {
        $query = 'query';
        $limit = 0;

        $this->emailRecipientsHelper->expects($this->never())
            ->method('addEmailsToContext');

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->loadContextEmails($event);
    }

    public function testLoadContextEmailsShouldNotAddStuffInContextIfRelatedActivityIsNull()
    {
        $query = 'query';
        $limit = 1;

        $this->emailRecipientsHelper->expects($this->never())
            ->method('addEmailsToContext');

        $event = new EmailRecipientsLoadEvent(null, $query, $limit);
        $this->emailRecipientsLoadListener->loadContextEmails($event);
    }

    public function testLoadContextEmailsShouldAddStuffInContext()
    {
        $query = 'query';
        $limit = 1;

        $emails = [
            'mail@example.com' => 'Mail <mail@example.com>',
        ];

        $relatedEntity = new Email();
        $event = new EmailRecipientsLoadEvent($relatedEntity, $query, $limit);

        $this->relatedEmailsProvider->expects($this->once())
            ->method('getEmails')
            ->with($relatedEntity, 2)
            ->will($this->returnValue($emails));

        $this->emailRecipientsHelper->expects($this->once())
            ->method('addEmailsToContext')
            ->with($event, $emails);

        $this->emailRecipientsLoadListener->loadContextEmails($event);
    }
}
