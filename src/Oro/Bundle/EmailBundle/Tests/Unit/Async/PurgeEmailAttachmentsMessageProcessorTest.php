<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class PurgeEmailAttachmentsMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new PurgeEmailAttachmentsMessageProcessor(
            $this->createRegistryInterfaceMock(),
            $this->createMessageProducerMock(),
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
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }
}
