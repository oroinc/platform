<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessageBodyResolverInterface;
use Oro\Component\MessageQueue\Consumption\Exception\InvalidMessageBodyException;

/**
 * Checks that the registered topics reject a message carrying a reserved option, so the consumer
 * rejects such a message instead of passing it to the handler.
 */
class ReservedOptionsRejectionTest extends WebTestCase
{
    private MessageBodyResolverInterface $messageBodyResolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->messageBodyResolver = self::getContainer()
            ->get('oro_message_queue.client.message_body_resolver');
    }

    public function testBodyWithBusinessOptionsIsResolvedUnchanged(): void
    {
        $options = ['price_list_id' => 1, 'writer_skip_clear' => true];

        $resolvedBody = $this->messageBodyResolver->resolveBody(
            PreExportTopic::getName(),
            ['jobName' => 'entity_export_to_csv', 'processorAlias' => 'oro_user', 'options' => $options]
        );

        self::assertSame($options, $resolvedBody['options']);
    }

    /**
     * @dataProvider topicDataProvider
     */
    public function testBodyWithReservedOptionIsRejected(string $topicName, array $body): void
    {
        $body['options'] = [Context::OPTION_FILE_PATH => '/tmp/test.csv'];

        $this->expectException(InvalidMessageBodyException::class);
        $this->expectExceptionMessage('The option "options" must not contain reserved keys: "filePath".');

        $this->messageBodyResolver->resolveBody($topicName, $body);
    }

    public function topicDataProvider(): array
    {
        return [
            'pre export' => [
                'topicName' => PreExportTopic::getName(),
                'body' => [
                    'jobName' => 'entity_export_to_csv',
                    'processorAlias' => 'oro_user',
                ],
            ],
            'export' => [
                'topicName' => ExportTopic::getName(),
                'body' => [
                    'jobName' => 'entity_export_to_csv',
                    'processorAlias' => 'oro_user',
                    'jobId' => 1,
                ],
            ],
            'pre import' => [
                'topicName' => PreImportTopic::getName(),
                'body' => [
                    'userId' => 1,
                    'jobName' => 'entity_import_from_csv',
                    'process' => 'import',
                    'processorAlias' => 'oro_account',
                    'fileName' => 'file.csv',
                    'originFileName' => 'origin.csv',
                ],
            ],
            'import' => [
                'topicName' => ImportTopic::getName(),
                'body' => [
                    'userId' => 1,
                    'jobName' => 'entity_import_from_csv',
                    'process' => 'import',
                    'processorAlias' => 'oro_account',
                    'fileName' => 'file.csv',
                    'originFileName' => 'origin.csv',
                    'jobId' => 1,
                ],
            ],
        ];
    }
}
