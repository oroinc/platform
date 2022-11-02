<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AuditChangedEntitiesInverseCollectionsChunkTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new AuditChangedEntitiesInverseCollectionsChunkTopic();
    }

    public function validBodyDataProvider(): array
    {
        $time = time();

        return [
            'required only' => [
                'body' => [
                    'jobId' => 42,
                    'entityData' => ['sample_key' => 'sample_value'],
                    'timestamp' => $time,
                    'transaction_id' => 142,
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'entityData' => ['sample_key' => 'sample_value'],
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'user_id' => null,
                    'user_class' => null,
                    'organization_id' => null,
                    'impersonation_id' => null,
                    'owner_description' => null,
                ],
            ],
            'all options' => [
                'body' => [
                    'jobId' => 42,
                    'entityData' => ['sample_key' => 'sample_value'],
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'user_id' => 1142,
                    'user_class' => \stdClass::class,
                    'organization_id' => 11142,
                    'impersonation_id' => 11142,
                    'owner_description' => 'sample description',
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'entityData' => ['sample_key' => 'sample_value'],
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'user_id' => 1142,
                    'user_class' => \stdClass::class,
                    'organization_id' => 11142,
                    'impersonation_id' => 11142,
                    'owner_description' => 'sample description',
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'jobId' => 42,
                    'entityData' => ['sample_key' => 'sample_value'],
                    'timestamp' => $time,
                    'transaction_id' => '142',
                    'user_id' => '1142',
                    'organization_id' => '11142',
                    'impersonation_id' => '11142',
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'entityData' => ['sample_key' => 'sample_value'],
                    'timestamp' => $time,
                    'transaction_id' => '142',
                    'user_id' => '1142',
                    'organization_id' => '11142',
                    'impersonation_id' => '11142',
                    'user_class' => null,
                    'owner_description' => null,
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "entityData", "jobId", "timestamp", '
                    . '"transaction_id" are missing./',
            ],
        ];
    }
}
