<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
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
        new Router($this->createDriverStub(), $this->createDestinationMetaRegistry());
    }

    public function testCouldBeConstructedWithSessionAndRoutes()
    {
        $routes = [
            'aTopicName' => [['aProcessorName', 'aQueueName']],
            'anotherTopicName' => [['aProcessorName', 'aQueueName']]
        ];

        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry(), $routes);

        $this->assertAttributeEquals($routes, 'routes', $router);
    }

    public function testThrowIfTopicNameEmptyOnOnAddRoute()
    {
        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry());

        $this->setExpectedException(\InvalidArgumentException::class, 'The topic name must not be empty');
        $router->addRoute('', 'aProcessorName', 'aQueueName');
    }

    public function testThrowIfProcessorNameEmptyOnOnAddRoute()
    {
        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry());

        $this->setExpectedException(\InvalidArgumentException::class, 'The processor name must not be empty');
        $router->addRoute('aTopicName', '', 'aQueueName');
    }

    public function testShouldAllowAddRouteWithQueueSetExplicitly()
    {
        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry());

        $router->addRoute('aTopicName', 'aProcessorName', 'aQueueName');

        $this->assertAttributeEquals(['aTopicName' => [['aProcessorName', 'aQueueName']]], 'routes', $router);
    }

    public function testShouldAllowAddTwoRoutesForSameTopic()
    {
        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry());

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
        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry());

        $router->addRoute('aTopicName', 'aProcessorName', 'default');

        $this->assertAttributeEquals(['aTopicName' => [['aProcessorName', 'default']]], 'routes', $router);
    }

    public function testShouldThrowExceptionIfTopicNameParameterIsNotSet()
    {
        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry());

        $this->setExpectedException(
            \LogicException::class,
            'Got message without required parameter: "oro.message_queue.client.topic_name"'
        );
        $result = $router->route(new NullMessage());

        iterator_to_array($result);
    }

    public function testThrowIfQueueNameEmptyOnOnAddRoute()
    {
        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry());

        $this->setExpectedException(\InvalidArgumentException::class, 'The queue name must not be empty');
        $router->addRoute('aTopicName', 'aProcessorName', '');
    }

    public function testShouldRouteOriginalMessageToRecipientAndDefaultQueue()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties(['aProp' => 'aPropVal', Config::PARAMETER_TOPIC_NAME => 'theTopicName']);

        $driver = $this->createDriverStub();

        $destinationsMeta = [
            'default' => []
        ];

        $router = new Router($driver, $this->createDestinationMetaRegistry($destinationsMeta));
        $router->addRoute('theTopicName', 'aFooProcessor', 'default');

        $result = $router->route($message);
        $result = iterator_to_array($result);

        $this->assertCount(1, $result);
        /** @var Recipient $recipient */
        $recipient = $result[0];
        $this->assertInstanceOf(Recipient::class, $recipient);

        $this->assertInstanceOf(NullQueue::class, $recipient->getDestination());
        $this->assertEquals('aprefix.adefaultqueuename', $recipient->getDestination()->getQueueName());

        $newMessage = $recipient->getMessage();
        $this->assertInstanceOf(NullMessage::class, $newMessage);
        $this->assertEquals('aprefix.adefaultqueuename', $newMessage->getProperty(Config::PARAMETER_QUEUE_NAME));
    }

    public function testShouldRouteOriginalMessageToRecipientToCustomQueue()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties(['aProp' => 'aPropVal', Config::PARAMETER_TOPIC_NAME => 'theTopicName']);

        $destinationsMeta = [
            'aFooQueue' => []
        ];

        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry($destinationsMeta));
        $router->addRoute('theTopicName', 'aFooProcessor', 'aFooQueue');

        $result = $router->route($message);
        $result = iterator_to_array($result);

        $this->assertCount(1, $result);
        /** @var Recipient $recipient */
        $recipient = $result[0];
        $this->assertInstanceOf(Recipient::class, $recipient);

        $this->assertInstanceOf(NullQueue::class, $recipient->getDestination());
        $this->assertEquals('aprefix.afooqueue', $recipient->getDestination()->getQueueName());

        $newMessage = $recipient->getMessage();
        $this->assertInstanceOf(NullMessage::class, $newMessage);
        $this->assertEquals('theBody', $newMessage->getBody());
        $this->assertEquals(
            [
                'aProp' => 'aPropVal',
                Config::PARAMETER_TOPIC_NAME => 'theTopicName',
                Config::PARAMETER_PROCESSOR_NAME => 'aFooProcessor',
                Config::PARAMETER_QUEUE_NAME => 'aprefix.afooqueue',
            ],
            $newMessage->getProperties()
        );
        $this->assertEquals(['aHeader' => 'aHeaderVal'], $newMessage->getHeaders());
    }

    public function testShouldRouteOriginalMessageToTwoRecipients()
    {
        $message = new NullMessage();
        $message->setProperties([Config::PARAMETER_TOPIC_NAME => 'theTopicName']);

        $destinationsMeta = [
            'aFooQueue' => [],
            'aBarQueue' => []
        ];

        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry($destinationsMeta));
        $router->addRoute('theTopicName', 'aFooProcessor', 'aFooQueue');
        $router->addRoute('theTopicName', 'aBarProcessor', 'aBarQueue');


        $result = $router->route($message);
        $result = iterator_to_array($result);

        $this->assertCount(2, $result);
        $this->assertContainsOnly(Recipient::class, $result);
    }

    public function testShouldRouteOriginalMessageToCustomTransportQueue()
    {
        $message = new NullMessage();
        $message->setProperties([Config::PARAMETER_TOPIC_NAME => 'theTopicName']);

        $destinationsMeta = [
            'aFooQueue' => ['transportName' => 'acustomqueue'],
        ];

        $router = new Router($this->createDriverStub(), $this->createDestinationMetaRegistry($destinationsMeta));
        $router->addRoute('theTopicName', 'aFooProcessor', 'aFooQueue');

        $result = $router->route($message);
        $result = iterator_to_array($result);

        $this->assertCount(1, $result);
        /** @var Recipient $recipient */
        $recipient = $result[0];
        $this->assertInstanceOf(Recipient::class, $recipient);

        $this->assertInstanceOf(NullQueue::class, $recipient->getDestination());
        $this->assertEquals('acustomqueue', $recipient->getDestination()->getQueueName());
    }

    /**
     * @param array $destinationsMeta
     *
     * @return DestinationMetaRegistry
     */
    protected function createDestinationMetaRegistry(array $destinationsMeta = [])
    {
        $config = new Config('aPrefix', 'aRouterMessageProcessorName', 'aRouterQueueName', 'aDefaultQueueName');

        return new DestinationMetaRegistry($config, $destinationsMeta, 'default');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected function createDriverStub()
    {
        $driverMock = $this->getMock(DriverInterface::class);
        $driverMock
            ->expects($this->any())
            ->method('createMessage')
            ->willReturn(new NullMessage())
        ;
        $driverMock
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function ($queueName) {
                return new NullQueue($queueName);
            })
        ;
        
        return $driverMock;
    }
}
