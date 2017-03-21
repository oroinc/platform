<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

trait MessageProcessTrait
{
    use MessageQueueExtension;

    /**
     * @return string
     */
    protected function processExportMessage(ContainerInterface $container, Client $client)
    {
        $sentMessages = $this->getSentMessages();
        $exportMessageData = reset($sentMessages);
        $this->clearMessageCollector();

        $message = new NullMessage();
        $message->setMessageId('abc');
        $message->setBody(json_encode($exportMessageData['message']));

        /** @var ExportMessageProcessor $processor */
        $processor = $container->get('oro_importexport.async.export');
        $processorResult = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::ACK, $processorResult);

        $sentMessages = $this->getSentMessages();
        foreach ($sentMessages as $messageData) {
            if (Topics::SEND_NOTIFICATION_EMAIL === $messageData['topic']) {
                break;
            }
        }

        preg_match('/http.*\.csv/', $messageData['message']['body'], $match);
        $urlChunks = explode('/', $match[0]);
        $filename = end($urlChunks);

        $client->request(
            'GET',
            $this->getUrl('oro_importexport_export_download', ['fileName' => $filename]),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );

        $result = $client->getResponse();

        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'text/csv');
        $this->assertStringStartsWith(
            'attachment; filename="' . $filename,
            $result->headers->get('Content-Disposition')
        );

        return $result->getFile()->getPathname();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionInterfaceMock()
    {
        return $this->getMockBuilder(SessionInterface::class)->getMock();
    }
}
