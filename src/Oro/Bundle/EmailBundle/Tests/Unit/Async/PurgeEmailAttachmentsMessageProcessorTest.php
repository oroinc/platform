<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;

class PurgeEmailAttachmentsMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new PurgeEmailAttachmentsMessageProcessor(
            $this->createRegistryInterfaceMock(),
            $this->createConfigManagerMock()
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::PURGE_EMAIL_ATTACHMENTS], PurgeEmailAttachmentsMessageProcessor::getSubscribedTopics());
    }

    /**
     * @dataProvider getSizeDataProvider
     */
    public function testShouldReturnCorrectAttachmentSizeByPayload($payload, $parameterSize, $expectedResult)
    {
        $configManager = $this->createConfigManagerMock();
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_email.attachment_sync_max_size')
            ->willReturn($parameterSize)
        ;

        $processor = new PurgeEmailAttachmentsMessageProcessor(
            $this->createRegistryInterfaceMock(),
            $configManager
        );

        $actualResult = ReflectionUtil::callProtectedMethod($processor, 'getSize', [$payload]);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function getSizeDataProvider()
    {
        return [
            [
                'payload' => ['size' => null, 'all' => true],
                'parameterSize' => 10,
                'result' => 0,
            ],
            [
                'payload' => ['size' => 2, 'all' => false],
                'parameterSize' => 10,
                'result' => 2000000,
            ],
            [
                'payload' => ['size' => null, 'all' => false],
                'parameterSize' => 3,
                'result' => 3000000,
            ],
        ];
    }

    /**
     * @dataProvider removeAttachmentDataProvider
     */
    public function testShouldRemoveCorrectAttachments($attachmentExpects, $attachmentReturns, $managerExpects, $size)
    {
        $attachment = $this->createEmailAttachmentMock();
        $attachment->expects($attachmentExpects)
            ->method('getSize')
            ->willReturn($attachmentReturns)
        ;

        $manager = $this->createEntityManagerMock();
        $manager->expects($managerExpects)
            ->method('remove')
            ->with($attachment)
        ;

        $processor = new PurgeEmailAttachmentsMessageProcessor(
            $this->createRegistryInterfaceMock(),
            $this->createConfigManagerMock()
        );

        ReflectionUtil::callProtectedMethod($processor, 'removeAttachment', [$manager, $attachment, $size]);
    }

    public function removeAttachmentDataProvider()
    {
        return [
            [
                'attachmentExpects' => $this->never(),
                'attachmentReturns' => 1,
                'managerExpects' => $this->once(),
                'size' => 0,
            ],
            [
                'attachmentExpects' => $this->once(),
                'attachmentReturns' => 1,
                'managerExpects' => $this->never(),
                'size' => 2,
            ],
            [
                'attachmentExpects' => $this->once(),
                'attachmentReturns' => 2,
                'managerExpects' => $this->once(),
                'size' => 2,
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->getMock(EntityManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->getMock(ConfigManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    private function createRegistryInterfaceMock()
    {
        return $this->getMock(RegistryInterface::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EmailAttachment
     */
    private function createEmailAttachmentMock()
    {
        return $this->getMock(EmailAttachment::class, [], [], '', false);
    }
}
