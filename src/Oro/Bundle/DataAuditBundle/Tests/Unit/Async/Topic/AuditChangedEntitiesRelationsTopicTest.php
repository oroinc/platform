<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesRelationsTopic;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AuditChangedEntitiesRelationsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new AuditChangedEntitiesRelationsTopic();
    }

    public function validBodyDataProvider(): array
    {
        $time = time();

        return [
            'required only' => [
                'body' => [
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'collections_updated' => ['sample_key' => 'sample_value'],
                ],
                'expectedBody' => [
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'collections_updated' => ['sample_key' => 'sample_value'],
                    'user_id' => null,
                    'user_class' => null,
                    'organization_id' => null,
                    'impersonation_id' => null,
                    'owner_description' => null,
                ],
            ],
            'all options' => [
                'body' => [
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'collections_updated' => ['sample_key' => 'sample_value'],
                    'user_id' => 1142,
                    'user_class' => \stdClass::class,
                    'organization_id' => 11142,
                    'impersonation_id' => 11142,
                    'owner_description' => 'sample description',
                ],
                'expectedBody' => [
                    'timestamp' => $time,
                    'transaction_id' => 142,
                    'collections_updated' => ['sample_key' => 'sample_value'],
                    'user_id' => 1142,
                    'user_class' => \stdClass::class,
                    'organization_id' => 11142,
                    'impersonation_id' => 11142,
                    'owner_description' => 'sample description',
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'timestamp' => $time,
                    'transaction_id' => '142',
                    'collections_updated' => ['sample_key' => 'sample_value'],
                    'user_id' => '1142',
                    'organization_id' => '11142',
                    'impersonation_id' => '11142',
                ],
                'expectedBody' => [
                    'timestamp' => $time,
                    'transaction_id' => '142',
                    'collections_updated' => ['sample_key' => 'sample_value'],
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
                'exceptionMessage' => '/The required options "collections_updated", "timestamp", '
                    . '"transaction_id" are missing./',
            ],
            'collections_updated must not be empty' => [
                'body' => [
                    'timestamp' => time(),
                    'transaction_id' => '142',
                    'collections_updated' => [],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The "collections_updated" was expected to be not empty./',
            ],
        ];
    }

    public function testDefaultPriority(): void
    {
        self::assertEquals(MessagePriority::VERY_LOW, $this->getTopic()->getDefaultPriority('queueName'));
    }
}
