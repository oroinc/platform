<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\PlatformBundle\Async\Topic\DeleteMaterializedViewTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeleteMaterializedViewTopicTest extends AbstractTopicTestCase
{
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'materializedViewName',
            ])
            ->setRequired([
                'materializedViewName',
            ])
            ->addAllowedTypes('materializedViewName', 'string');
    }

    protected function getTopic(): TopicInterface
    {
        return new DeleteMaterializedViewTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            [
                'body' => ['materializedViewName' => 'sample-name'],
                'expectedBody' => ['materializedViewName' => 'sample-name'],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "materializedViewName" is missing\./',
            ],
        ];
    }
}
