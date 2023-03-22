<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExportTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ExportTopic($this->createMock(TokenStorageInterface::class));
    }

    public function validBodyDataProvider(): array
    {
        $fullOptionsSet = [
            'jobId' => 1,
            'jobName' => 'foo',
            'processorAlias' => 'baz',
            'outputFormat' => 'output_format',
            'organizationId' => 1,
            'exportType' => ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
            'options' => [
                'foo' => 'bar',
            ],
            'outputFilePrefix' => 'prefix',
            'entity' => 'entityName',
        ];

        return [
            'only required options' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'processorAlias' => 'baz',
                ],
                'expectedBody' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'processorAlias' => 'baz',
                    'outputFormat' => 'csv',
                    'exportType' => ProcessorRegistry::TYPE_EXPORT,
                    'options' => [],
                    'outputFilePrefix' => null,
                ],
            ],
            'full set of options' => [
                'body' => $fullOptionsSet,
                'expectedBody' => $fullOptionsSet,
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "jobId", "jobName", "processorAlias" are missing./',
            ],
            'wrong jobName type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => null,
                    'processorAlias' => 'baz',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "jobName" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong processorAlias type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'processorAlias' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "processorAlias" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong outputFormat type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'processorAlias' => 'bar',
                    'outputFormat' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "outputFormat" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong organizationId type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'processorAlias' => 'bar',
                    'organizationId' => new \stdClass(),
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "organizationId" with value stdClass is expected '
                    .'to be of type "int" or "null"/',
            ],
            'wrong exportType type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'processorAlias' => 'bar',
                    'exportType' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "exportType" with value null is expected to be of type "string", '
                    . 'but is of type "null"./',
            ],
            'wrong options type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'processorAlias' => 'bar',
                    'options' => null,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "options" with value null is expected to be of type "array", '
                    . 'but is of type "null"./',
            ],
            'wrong jobId type' => [
                'body' => [
                    'jobId' => null,
                    'jobName' => 'foo',
                    'processorAlias' => 'bar',
                    'organizationId' => 1,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value null is expected to be of type "int"/',
            ],
            'wrong outputFilePrefix type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'processorAlias' => 'bar',
                    'outputFilePrefix' => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "outputFilePrefix" with value array is expected to be of type "string" or "null", '
                    . 'but is of type "array"./',
            ],
            'wrong entity type' => [
                'body' => [
                    'jobId' => 1,
                    'jobName' => 'foo',
                    'processorAlias' => 'bar',
                    'entity' => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "entity" with value array is expected to be of type "string" or "null", '
                    . 'but is of type "array"./',
            ],
        ];
    }
}
