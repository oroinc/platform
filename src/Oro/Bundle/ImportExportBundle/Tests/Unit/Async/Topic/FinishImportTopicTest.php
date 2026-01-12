<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\Topic\FinishImportTopic;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class FinishImportTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new FinishImportTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'parameters' => [
                'body' => [
                    'rootImportJobId' => 1,
                    'processorAlias' => 'processor_alias',
                    'type' => ProcessorRegistry::TYPE_IMPORT,
                    'options' => [],
                ],
                'expectedBody' => [
                    'rootImportJobId' => 1,
                    'processorAlias' => 'processor_alias',
                    'type' => ProcessorRegistry::TYPE_IMPORT,
                    'options' => [],
                ],
            ],
            'parameters with import validation type' => [
                'body' => [
                    'rootImportJobId' => 1,
                    'processorAlias' => 'processor_alias',
                    'type' => ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                    'options' => null,
                ],
                'expectedBody' => [
                    'rootImportJobId' => 1,
                    'processorAlias' => 'processor_alias',
                    'type' => ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                    'options' => [],
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'parameters with invalid "rootImportJobId" option' => [
                'body' => [
                    'rootImportJobId' => '1',
                    'processorAlias' => 123,
                    'type' => ProcessorRegistry::TYPE_EXPORT,
                    'options' => 123,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "rootImportJobId" with value "1" is expected to be of type "int",' .
                    ' but is of type "string"./'
            ],
            'parameters with invalid "processorAlias" option' => [
                'body' => [
                    'rootImportJobId' => 1,
                    'processorAlias' => 123,
                    'type' => ProcessorRegistry::TYPE_EXPORT,
                    'options' => 123,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "processorAlias" with value 123 is expected to be of type ' .
                    '"string", but is of type "int"./'
            ],
            'parameters with invalid "type" option' => [
                'body' => [
                    'rootImportJobId' => 1,
                    'processorAlias' => 'processor_alias',
                    'type' => ProcessorRegistry::TYPE_EXPORT,
                    'options' => 123,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "type" with value "export" is invalid. ' .
                    'Accepted values are: "import", "import_validation"./'
            ],
            'parameters with invalid "options" option' => [
                'body' => [
                    'rootImportJobId' => 1,
                    'processorAlias' => 'processor_alias',
                    'type' => ProcessorRegistry::TYPE_IMPORT,
                    'options' => 123,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "options" with value 123 is expected to be of type ' .
                    '"array" or "null", but is of type "int"./'
            ],
        ];
    }
}
