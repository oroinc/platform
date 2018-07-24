<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PurgeEmailAttachmentsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new PurgeEmailAttachmentsMessageProcessor(
            $this->createRegistryInterfaceMock(),
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $this->createConfigManagerMock()
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [ Topics::PURGE_EMAIL_ATTACHMENTS ],
            PurgeEmailAttachmentsMessageProcessor::getSubscribedTopics()
        );
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
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
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
     * @return \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->createMock(ConfigManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RegistryInterface
     */
    private function createRegistryInterfaceMock()
    {
        return $this->createMock(RegistryInterface::class);
    }


    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMockBuilder(JobRunner::class)->disableOriginalConstructor()->getMock();
    }


    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }
}
