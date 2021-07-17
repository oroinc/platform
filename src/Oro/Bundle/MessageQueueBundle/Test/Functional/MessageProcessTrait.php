<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics as ImportExportTopics;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Trait for testing processors with import/export logic.
 */
trait MessageProcessTrait
{
    use MessageQueueExtension;

    protected function processExportMessage(ContainerInterface $container, Client $client): string
    {
        $sentMessage = $this->getSentMessage(ImportExportTopics::PRE_EXPORT);
        $this->clearMessageCollector();

        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody(json_encode($sentMessage));

        $session = $this->createMock(SessionInterface::class);

        /** @var ExportMessageProcessor $processor */
        $processor = $container->get('oro_importexport.async.export');
        $processorResult = $processor->process($message, $session);

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
}
