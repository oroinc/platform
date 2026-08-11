<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\DataAuditBundle\Async\Topic\ConfigChangeAuditTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ConfigChangeAuditTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new ConfigChangeAuditTopic();
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        $time = time();

        return [
            'required only' => [
                'body' => [
                    'timestamp' => $time,
                    'transaction_id' => 'tx-1',
                    'object_class' => 'Oro\Bundle\ConfigBundle\SystemConfiguration',
                    'object_id' => '0',
                    'object_name' => 'Global',
                    'action' => 'update',
                    'changes' => ['oro_test.foo' => ['field' => 'Foo', 'old' => 'a', 'new' => 'b']],
                ],
                'expectedBody' => [
                    'timestamp' => $time,
                    'transaction_id' => 'tx-1',
                    'object_class' => 'Oro\Bundle\ConfigBundle\SystemConfiguration',
                    'object_id' => '0',
                    'object_name' => 'Global',
                    'action' => 'update',
                    'changes' => ['oro_test.foo' => ['field' => 'Foo', 'old' => 'a', 'new' => 'b']],
                    'user_id' => null,
                    'user_class' => null,
                    'organization_id' => null,
                    'impersonation_id' => null,
                    'owner_description' => null,
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options .+ are missing\./',
            ],
        ];
    }
}
