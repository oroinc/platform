<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Router\Recipient;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Router\RecipientListRouterInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\Router;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementRecipientListRouterInterface()
    {
        $this->assertClassImplements(RecipientListRouterInterface::class, Router::class);
    }

    public function testCouldBeConstructedWithSessionAsFirstArgument()
    {
        new Router($this->createSessionStub());
    }

    public function testCouldBeConstructedWithSessionAndRoutes()
    {
        $routes = [
            'aTopicName' => [['aProcessorName', 'aQueueName']],
            'anotherTopicName' => [['aProcessorName', 'aQueueName']]
        ];

        $router = new Router($this->createSessionStub(), $routes);

        $this->assertAttributeEquals($routes, 'routes', $router);
    }

    public function testThrowIfTopicNameEmptyOnOnAddRoute()
    {
        $router = new Router($this->createSessionStub());

        $this->setExpectedException(\InvalidArgumentException::class, 'The topic name must not be empty');
        $router->addRoute('', 'aProcessorName', 'aQueueName');
    }

    public function testThrowIfProcessorNameEmptyOnOnAddRoute()
    {
        $router = new Router($this->createSessionStub());

        $this->setExpectedException(\InvalidArgumentException::class, 'The processor name must not be empty');
        $router->addRoute('aTopicName', '', 'aQueueName');
    }

    public function testShouldAllowAddRouteWithQueueSetExplicitly()
    {
        $router = new Router($this->createSessionStub());

        $router->addRoute('aTopicName', 'aProcessorName', 'aQueueName');

        $this->assertAttributeEquals(['aTopicName' => [['aProcessorName', 'aQueueName']]], 'routes', $router);
    }

    public function testShouldAllowAddTwoRoutesForSameTopic()
    {
        $router = new Router($this->createSessionStub());

        $router->addRoute('aTopicName', 'aFooProcessorName', 'aFooQueueName');
        $router->addRoute('aTopicName', 'aBarProcessorName', 'aBarQueueName');

        $this->assertAttributeEquals(
            ['aTopicName' => [['aFooProcessorName', 'aFooQueueName'], ['aBarProcessorName', 'aBarQueueName']]],
            'routes',
            $router
        );
    }

    public function testShouldAllowAddRouteWithDefaultQueue()
    {
        $router = new Router($this->createSessionStub());

        $router->addRoute('aTopicName', 'aProcessorName');

        $this->assertAttributeEquals(['aTopicName' => [['aProcessorName', null]]], 'routes', $router);
    }

    public function testShouldThrowExceptionIfTopicNameParameterIsNotSet()
    {
        $router = new Router($this->createSessionStub());

        $this->setExpectedException(
            \LogicException::class,
            'Got message without required parameter: "oro.message_queue.client.topic_name"'
        );
        $result = $router->route(new NullMessage());

        iterator_to_array($result);
    }

    public function testShouldRouteOriginalMessageToRecipient()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties(['aProp' => 'aPropVal', Config::PARAMETER_TOPIC_NAME => 'theTopicName']);

        $router = new Router($this->createSessionStub());
        $router->addRoute('theTopicName', 'aFooProcessor', 'aFooQueue');

        $result = $router->route($message);
        $result = iterator_to_array($result);

        $this->assertCount(1, $result);
        /** @var Recipient $recipient */
        $recipient = $result[0];
        $this->assertInstanceOf(Recipient::class, $recipient);

        $this->assertInstanceOf(NullQueue::class, $recipient->getDestination());
        $this->assertEquals('aFooQueue', $recipient->getDestination()->getQueueName());

        $newMessage = $recipient->getMessage();
        $this->assertInstanceOf(NullMessage::class, $newMessage);
        $this->assertEquals('theBody', $newMessage->getBody());
        $this->assertEquals(
            [
                'aProp' => 'aPropVal',
                Config::PARAMETER_TOPIC_NAME => 'theTopicName',
                Config::PARAMETER_PROCESSOR_NAME => 'aFooProcessor',
                Config::PARAMETER_QUEUE_NAME => 'aFooQueue',
            ],
            $newMessage->getProperties()
        );
        $this->assertEquals(['aHeader' => 'aHeaderVal'], $newMessage->getHeaders());
    }

    public function testShouldRouteOriginalMessageToRecipientAndDefaultQueue()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties(['aProp' => 'aPropVal', Config::PARAMETER_TOPIC_NAME => 'theTopicName']);

        $config = new Config('aPrefix', 'aRouterMessageProcessorName', 'routerQueueName', 'defaultQueueName');
        $session = $this->createSessionStub($config);

        $router = new Router($session);
        $router->addRoute('theTopicName', 'aFooProcessor');

        $result = $router->route($message);
        $result = iterator_to_array($result);

        $this->assertCount(1, $result);
        /** @var Recipient $recipient */
        $recipient = $result[0];
        $this->assertInstanceOf(Recipient::class, $recipient);

        $this->assertInstanceOf(NullQueue::class, $recipient->getDestination());
        $this->assertEquals('aprefix.defaultqueuename', $recipient->getDestination()->getQueueName());

        $newMessage = $recipient->getMessage();
        $this->assertInstanceOf(NullMessage::class, $newMessage);
        $this->assertEquals('aprefix.defaultqueuename', $newMessage->getProperty(Config::PARAMETER_QUEUE_NAME));
    }

    public function testShouldRouteOriginalMessageToTwoRecipients()
    {
        $message = new NullMessage();
        $message->setProperties([Config::PARAMETER_TOPIC_NAME => 'theTopicName']);

        $router = new Router($this->createSessionStub());
        $router->addRoute('theTopicName', 'aFooProcessor', 'aFooQueue');
        $router->addRoute('theTopicName', 'aBarProcessor', 'aBarQueue');


        $result = $router->route($message);
        $result = iterator_to_array($result);

        $this->assertCount(2, $result);
        $this->assertContainsOnly(Recipient::class, $result);
    }
    
    protected function createSessionStub($config = null)
    {
        $sessionMock = $this->getMock(DriverInterface::class);
        $sessionMock
            ->expects($this->any())
            ->method('createMessage')
            ->willReturn(new NullMessage())
        ;
        $sessionMock
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function ($queueName) {
                return new NullQueue($queueName);
            })
        ;
        $sessionMock
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config)
        ;
        
        return $sessionMock;
    }
}
