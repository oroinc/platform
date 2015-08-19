<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Form\Model\Factory;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Provider\EmailAttachmentProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class EmailModelBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailModelBuilder
     */
    protected $emailModelBuilder;

    /**
     * @var EmailModelBuilderHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var EmailActivityListProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $activityListProvider;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;
    
    /**
     * @var EmailAttachmentProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailAttachmentProvider;

    /**
     * @var Email|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $email;

    /**
     * @var EmailAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fromEmailAddress;

    /**
     * @var EmailAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fromCcEmailAddress;

    /**
     * @var Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    protected function setUp()
    {
        $this->request = new Request();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->activityListProvider = $this->getMockBuilder(
            'Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAttachmentProvider = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailAttachmentProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAttachmentProvider->expects($this->any())
            ->method('getThreadAttachments')
            ->willReturn([]);

        $this->emailAttachmentProvider->expects($this->any())
            ->method('getScopeEntityAttachments')
            ->willReturn([]);

        $this->email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->setMethods(
                ['getActivityTargetEntities', 'getFromEmailAddress', 'getId', 'getTo', 'getCc', 'getEmailBody']
            )
            ->getMock();

        $this->email->expects($this->any())
            ->method('getActivityTargetEntities')
            ->willReturn([]);

        $this->factory = new Factory();

        $this->emailModelBuilder = new EmailModelBuilder(
            $this->helper,
            $this->entityManager,
            $this->configManager,
            $this->activityListProvider,
            $this->emailAttachmentProvider,
            $this->factory
        );

        $this->emailModelBuilder->setRequest($this->request);
    }

    /**
     * @param $entityClass
     * @param $entityId
     * @param $from
     * @param $subject
     * @param $parentEmail
     * @param $helperDecodeClassNameCalls
     * @param $emGetRepositoryCalls
     * @param $helperPreciseFullEmailAddressCalls
     * @param $helperGetUserCalls
     * @param $helperBuildFullEmailAddress
     *
     * @dataProvider createEmailModelProvider
     *
     * @SuppressWarnings(PHPMD)
     */
    public function testCreateEmailModel(
        $entityClass,
        $entityId,
        $from,
        $subject,
        $parentEmail,
        $helperDecodeClassNameCalls,
        $emGetRepositoryCalls,
        $helperPreciseFullEmailAddressCalls,
        $helperGetUserCalls,
        $helperBuildFullEmailAddress
    ) {
        $emailModel = new EmailModel();

        $this->request = new Request();
        $this->request->setMethod('GET');

        if ($entityClass) {
            $this->request->query->set('entityClass', $entityClass);
        }
        if ($entityId) {
            $this->request->query->set('entityId', $entityId);
        }
        if ($from) {
            $this->request->query->set('from', $from);
        }
        if ($subject) {
            $this->request->query->set('subject', $subject);
        }

        $this->emailModelBuilder = new EmailModelBuilder(
            $this->helper,
            $this->entityManager,
            $this->configManager,
            $this->activityListProvider,
            $this->emailAttachmentProvider,
            $this->factory
        );

        $this->emailModelBuilder->setRequest($this->request);

        $this->helper->expects($this->exactly($helperDecodeClassNameCalls))
            ->method('decodeClassName')
            ->willReturn($entityClass);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->exactly($emGetRepositoryCalls))
            ->method('getRepository')
            ->willReturn($repository);

        $this->helper->expects($this->exactly($helperPreciseFullEmailAddressCalls))
            ->method('preciseFullEmailAddress');

        $this->helper->expects($this->exactly($helperGetUserCalls))
            ->method('getUser')
            ->willReturn($this->getMock('Oro\Bundle\UserBundle\Entity\User'));

        $this->helper->expects($this->exactly($helperBuildFullEmailAddress))
            ->method('buildFullEmailAddress');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_email.signature');

        $result = $this->emailModelBuilder->createEmailModel($emailModel);
        $this->assertEquals($emailModel, $result);

        $this->assertEquals($entityClass, $result->getEntityClass());
        $this->assertEquals($entityId, $result->getEntityId());
        $this->assertEquals($subject, $result->getSubject());
        $this->assertEquals($from, $result->getFrom());
    }

    public function createEmailModelProvider()
    {
        return [
            [
                'entityClass' => 'Oro\Bundle\UserBundle\Entity\User',
                'entityId' => 1,
                'from' => 'from@example.com',
                'subject' => 'Subject',
                'parentEmailId' => $this->email,
                'helperDecodeClassNameCalls' => 1,
                'emGetRepositoryCalls' => 1,
                'helperPreciseFullEmailAddressCalls' => 1,
                'helperGetUserCalls' => 0,
                'helperBuildFullEmailAddress' => 0,
            ],
            [
                'entityClass' => null,
                'entityId' => null,
                'from' => null,
                'subject' => null,
                'parentEmailId' => null,
                'helperDecodeClassNameCalls' => 0,
                'emGetRepositoryCalls' => 0,
                'helperPreciseFullEmailAddressCalls' => 0,
                'helperGetUserCalls' => 2,
                'helperBuildFullEmailAddress' => 2,
            ],
        ];
    }

    /**
     * @param object $getOwnerResult
     * @param object $getUserResult
     * @param int    $getToCalls
     *
     * @dataProvider createReplyEmailModelProvider
     */
    public function testCreateReplyEmailModel($getOwnerResult, $getUserResult, $getToCalls)
    {
        $this->fromEmailAddress = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAddress');

        $this->fromEmailAddress->expects($this->once())
            ->method('getOwner')
            ->willReturn($getOwnerResult);

        $this->helper->expects($this->any())
            ->method('getUser')
            ->willReturn($getUserResult);

        $getUserResult->expects($this->any())
            ->method('getEmails')
            ->willReturn([]);

        $this->email->expects($this->once())
            ->method('getFromEmailAddress')
            ->willReturn($this->fromEmailAddress);

        $this->email->expects($this->any())
            ->method('getId');

        $emailAddress = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAddress');
        $emailAddress->expects($this->exactly($getToCalls))
            ->method('getEmail')
            ->willReturn(null);

        $emailRecipient = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
        $emailRecipient->expects($this->exactly($getToCalls))
            ->method('getEmailAddress')
            ->willReturn($emailAddress);

        $to = new ArrayCollection();
        $to->add($emailRecipient);

        $this->email->expects($this->exactly($getToCalls))
            ->method('getTo')
            ->willReturn($to);

        $this->helper->expects($this->once())
            ->method('prependWith');

        $this->helper->expects($this->once())
            ->method('getEmailBody');
        $this->activityListProvider->expects($this->once())
            ->method('getTargetEntities')
            ->willReturn([]);

        $result = $this->emailModelBuilder->createReplyEmailModel($this->email);
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
    }

    public function createReplyEmailModelProvider()
    {
        $entityOne = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $entityTwo = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        return [
            [$entityOne, $entityTwo, 1],
            [$entityTwo, $entityOne, 1],
            [$entityOne, $entityOne, 1],
            [$entityTwo, $entityTwo, 1],
        ];
    }

    public function testCreateForwardEmailModel()
    {
        $this->helper->expects($this->once())
            ->method('prependWith');

        $emailBody = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailBody');
        $emailBody->expects($this->exactly(1))
            ->method('getAttachments')
            ->willReturn([]);

        $this->email->expects($this->once())
            ->method('getEmailBody')
            ->willReturn($emailBody);

        $result = $this->emailModelBuilder->createForwardEmailModel($this->email);
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
    }

    /**
     * @param object $getOwnerResult
     * @param object $getUserResult
     * @param int    $getToCalls
     *
     * @dataProvider createReplyEmailModelProvider
     */
    public function testCreateReplyAllEmailModel($getOwnerResult, $getUserResult, $getToCalls)
    {
        $this->fromEmailAddress = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAddress');
        $this->fromCcEmailAddress = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAddress');

        $this->fromEmailAddress->expects($this->once())
            ->method('getOwner')
            ->willReturn($getOwnerResult);

        $this->helper->expects($this->any())
            ->method('getUser')
            ->willReturn($getUserResult);

        $getUserResult->expects($this->any())
            ->method('getEmails')
            ->willReturn([]);

        $this->email->expects($this->once())
            ->method('getFromEmailAddress')
            ->willReturn($this->fromEmailAddress);

        $this->email->expects($this->any())
            ->method('getId');

        $emailAddress = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAddress');
        $emailAddress->expects($this->exactly($getToCalls))
            ->method('getEmail')
            ->willReturn(null);

        $emailRecipient = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
        $emailRecipient->expects($this->exactly($getToCalls))
            ->method('getEmailAddress')
            ->willReturn($emailAddress);

        $to = new ArrayCollection();
        $to->add($emailRecipient);

        $this->email->expects($this->exactly($getToCalls))
            ->method('getTo')
            ->willReturn($to);

        $emailCcRecipient = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
        $emailCcRecipient->expects($this->once())
            ->method('getEmailAddress')
            ->willReturn($this->fromCcEmailAddress);

        $cc = new ArrayCollection();
        $cc->add($emailCcRecipient);

        $this->email->expects($this->exactly($getToCalls))
            ->method('getCc')
            ->willReturn($cc);

        $this->helper->expects($this->once())
            ->method('prependWith');

        $this->helper->expects($this->once())
            ->method('getEmailBody');
        $this->activityListProvider->expects($this->once())
            ->method('getTargetEntities')
            ->willReturn([]);

        $result = $this->emailModelBuilder->createReplyAllEmailModel($this->email);
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
    }
}
