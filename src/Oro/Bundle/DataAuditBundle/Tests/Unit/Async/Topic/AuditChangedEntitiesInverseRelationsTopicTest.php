<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseRelationsTopic;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AuditChangedEntitiesInverseRelationsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new AuditChangedEntitiesInverseRelationsTopic();
    }

    public function validBodyDataProvider(): array
    {
        $time = time();

        return [
            'required only' => [
                'body' => [
                    'timestamp' => $time,
                    'transaction_id' => 142,
                ],
                'expectedBody' => [
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'user_id' => null,
                    'user_class' => null,
                    'organization_id' => null,
                    'impersonation_id' => null,
                    'owner_description' => null,
                    'entities_inserted' => [],
                    'entities_updated' => [],
                    'entities_deleted' => [],
                    'collections_updated' => [],
                ],
            ],
            'all options' => [
                'body' => [
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'user_id' => 1142,
                    'user_class' => \stdClass::class,
                    'organization_id' => 11142,
                    'impersonation_id' => 11142,
                    'owner_description' => 'sample description',
                    'entities_inserted' => ['sample_key' => 'sample_value'],
                    'entities_updated' => ['sample_key' => 'sample_value'],
                    'entities_deleted' => ['sample_key' => 'sample_value'],
                    'collections_updated' => ['sample_key' => 'sample_value'],
                ],
                'expectedBody' => [
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'user_id' => 1142,
                    'user_class' => \stdClass::class,
                    'organization_id' => 11142,
                    'impersonation_id' => 11142,
                    'owner_description' => 'sample description',
                    'entities_inserted' => ['sample_key' => 'sample_value'],
                    'entities_updated' => ['sample_key' => 'sample_value'],
                    'entities_deleted' => ['sample_key' => 'sample_value'],
                    'collections_updated' => ['sample_key' => 'sample_value'],
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'timestamp' => $time,
                    'transaction_id' => '142',
                    'user_id' => '1142',
                    'organization_id' => '11142',
                    'impersonation_id' => '11142',
                ],
                'expectedBody' => [
                    'timestamp' => $time,
                    'transaction_id' => '142',
                    'user_id' => '1142',
                    'organization_id' => '11142',
                    'impersonation_id' => '11142',
                    'user_class' => null,
                    'owner_description' => null,
                    'entities_inserted' => [],
                    'entities_updated' => [],
                    'entities_deleted' => [],
                    'collections_updated' => [],
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
                'exceptionMessage' => '/The required options "timestamp", "transaction_id" are missing./',
            ],
        ];
    }

    public function testDefaultPriority(): void
    {
        self::assertEquals(MessagePriority::VERY_LOW, $this->getTopic()->getDefaultPriority('queueName'));
    }
}
