<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;

class EmailModelBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailModelBuilder|\PHPUnit_Framework_MockObject_MockObject
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
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->request = new Request();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailModelBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailModelBuilder')
            ->setConstructorArgs([$this->helper, $this->request, $this->entityManager])
            ->setMethods(['applyRequest'])
            ->getMock();
    }

    /**
     * @param string $method
     * @param int    $applyRequestCalls
     *
     * @dataProvider createEmailMethodCallProvider
     */
    public function testCreateEmailModel($method, $applyRequestCalls)
    {
        $emailModel = new EmailModel();

        $this->request->setMethod($method);

        $this->emailModelBuilder->expects($this->exactly($applyRequestCalls))
            ->method('applyRequest');

        $result = $this->emailModelBuilder->createEmailModel($emailModel);
        $this->assertEquals($emailModel, $result);
    }

    public function createEmailMethodCallProvider()
    {
        return [
            ['GET', 1],
            ['POST', 0],
        ];
    }

    public function testCreateEmailModelFromScratch()
    {
        $result = $this->emailModelBuilder->createEmailModel();
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
    }

    /**
     * @param boolean $getOwnerResult
     * @param boolean $getUserResult
     * @param $getToCalls
     *
     * @dataProvider createReplyEmailModelProvider
     */
    public function testCreateReplyEmailModel($getOwnerResult, $getUserResult, $getToCalls)
    {
        $fromEmailAddress = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAddress');

        $fromEmailAddress->expects($this->once())
            ->method('getOwner')
            ->willReturn($getOwnerResult);

        $this->helper->expects($this->once())
            ->method('getUser')
            ->willReturn($getUserResult);

        $parentEmailEntity = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');

        $parentEmailEntity->expects($this->once())
            ->method('getFromEmailAddress')
            ->willReturn($fromEmailAddress);

        $parentEmailEntity->expects($this->once())
            ->method('getId');

        $parentEmailEntity->expects($this->once())
            ->method('getFromEmailAddress');

        $emailAddress = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAddress');
        $emailAddress->expects($this->exactly($getToCalls))
            ->method('getEmail')
            ->willReturn(null);

        $emailRecipient = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailRecipient');
        $emailRecipient->expects($this->exactly($getToCalls))
            ->method('getEmailAddress')
            ->willReturn($emailAddress);

        $to = $this->getMock('Doctrine\Common\Collections\Collection');
        $to->expects($this->exactly($getToCalls))
            ->method('first')
            ->willReturn($emailRecipient);

        $parentEmailEntity->expects($this->exactly($getToCalls))
            ->method('getTo')
            ->willReturn($to);

        $this->helper->expects($this->once())
            ->method('prependWith');

        $this->helper->expects($this->once())
            ->method('getEmailBody');

        $result = $this->emailModelBuilder->createReplyEmailModel($parentEmailEntity);
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
    }

    public function createReplyEmailModelProvider()
    {
        return [
            [true, false, 0],
            [false, true, 0],
            [false, false, 1],
            [true, true, 1],
        ];
    }

    public function testCreateForwardEmailModel()
    {
        $parentEmailEntity = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');

        $parentEmailEntity->expects($this->once())
            ->method('getId');

        $this->helper->expects($this->once())
            ->method('prependWith');

        $this->helper->expects($this->once())
            ->method('getEmailBody');

        $result = $this->emailModelBuilder->createForwardEmailModel($parentEmailEntity);
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
    }
}
