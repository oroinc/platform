<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async;

use Oro\Bundle\ImportExportBundle\Async\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class ExportMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_importexport.async.export');

        $this->assertInstanceOf(ExportMessageProcessor::class, $instance);
    }

    /**
     * @dataProvider exportProcessDataProvider
     */
    public function testShouldSendNotificationMessageWithExportResult(
        $resultSuccess,
        $resultReadsCount,
        $resultErrorsCount,
        $expectedEmailBody,
        $expectedProcessResult
    ) {
        /** @var User $user */
        $user = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityRepository(User::class)->find(1);

        $message = new NullMessage();
        $message->setMessageId('abc');
        $message->setBody(json_encode([
            'jobName' => 'job_name',
            'processorAlias' => 'alias',
            'userId' => $user->getId(),
        ]));

        $exportHandler = $this->createExportHandlerMock();
        $exportHandler
            ->expects($this->once())
            ->method('getExportResult')
            ->with(
                $this->equalTo('job_name'),
                $this->equalTo('alias'),
                $this->equalTo(ProcessorRegistry::TYPE_EXPORT),
                $this->equalTo('csv'),
                $this->equalTo(null),
                $this->equalTo([])
            )
            ->willReturn([
                'success' => $resultSuccess,
                'url' => 'http://localhost',
                'readsCount' => $resultReadsCount,
                'errorsCount' => $resultErrorsCount,
                'entities' => 'User',
            ])
        ;

        $this->getContainer()->set('oro_importexport.handler.export', $exportHandler);

        $processor = $this->getContainer()->get('oro_importexport.async.export');

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals($expectedProcessResult, $result);

        $this->assertMessageSent(NotificationTopics::SEND_NOTIFICATION_EMAIL, [
            'fromEmail' => $this->getConfigManager()->get('oro_notification.email_notification_sender_email'),
            'fromName' => $this->getConfigManager()->get('oro_notification.email_notification_sender_name'),
            'toEmail' => $user->getEmail(),
            'subject' => 'Export result for job oro.importexport.export_alias',
            'body' => $expectedEmailBody,
        ]);
    }

    public function exportProcessDataProvider()
    {
        return [
            [
                'resultSuccess' => true,
                'readsCount' => 100,
                'errorsCount' => 0,
                'emailBody' => 'Export performed successfully, 100 User were exported. Download link: http://localhost',
                'processResult' => ExportMessageProcessor::ACK,
            ], [
                'resultSuccess' => true,
                'readsCount' => 0,
                'errorsCount' => 0,
                'emailBody' => 'No User found for export.',
                'processResult' => ExportMessageProcessor::ACK,
            ], [
                'resultSuccess' => false,
                'readsCount' => 0,
                'errorsCount' => 5,
                'emailBody' => 'Export operation fails, 5 error(s) found. Error log: http://localhost',
                'processResult' => ExportMessageProcessor::REJECT,
            ],
        ];
    }

    /**
     * @return object
     */
    private function getConfigManager()
    {
        return $this->getContainer()->get('oro_config.user');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->getMock(ExportHandler::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }
}
