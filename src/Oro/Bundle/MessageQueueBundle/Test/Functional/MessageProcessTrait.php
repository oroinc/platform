<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
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
        $sentMessage = self::getSentMessage(PreExportTopic::getName());
        self::clearMessageCollector();

        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody(json_encode($sentMessage));

        $session = $this->createMock(SessionInterface::class);

        /** @var ExportMessageProcessor $processor */
        $processor = $container->get('oro_importexport.async.export');
        $processorResult = $processor->process($message, $session);

        $this->assertEquals(ExportMessageProcessor::ACK, $processorResult);

        $sentMessages = self::getSentMessages();
        foreach ($sentMessages as $messageData) {
            if (SendEmailNotificationTemplateTopic::getName() === $messageData['topic']) {
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
            self::generateNoHashNavigationHeader()
        );

        $result = $client->getResponse();

        self::assertResponseStatusCodeEquals($result, 200);
        self::assertResponseContentTypeEquals($result, 'text/csv');
        self::assertStringStartsWith(
            'attachment; filename="' . $filename,
            $result->headers->get('Content-Disposition')
        );

        return $result->getFile()->getPathname();
    }
}
