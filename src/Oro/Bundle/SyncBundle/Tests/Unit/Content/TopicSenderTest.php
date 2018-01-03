<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content;

use Psr\Log\LoggerInterface;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\SyncBundle\Content\TagGeneratorChain;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\SyncBundle\Content\TopicSender;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class TopicSenderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_USERNAME = 'usernameTeST';
    const TEST_TAG1     = 'TestTag1';
    const TEST_TAG2     = 'TestTag2';

    /** @var TopicPublisher|\PHPUnit_Framework_MockObject_MockObject */
    protected $publisher;

    /** @var TagGeneratorChain */
    protected $generator;

    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $tokenStorage;

    /** @var TopicSender */
    protected $sender;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    protected function setUp()
    {
        $this->publisher = $this->createMock(TopicPublisher::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->generator = new TagGeneratorChain();
        $container = new Container();
        $container->set('generator', $this->generator);

        $this->sender = new TopicSender(
            $this->publisher,
            new ServiceLink($container, 'generator'),
            $this->tokenStorage,
            $this->logger
        );
    }

    public function testGetGenerator()
    {
        self::assertSame($this->generator, $this->sender->getGenerator());
    }

    public function testSendWithLoggedInUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(UserInterface::class);

        $this->tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($user);
        $user->expects(self::any())
            ->method('getUserName')
            ->willReturn(self::TEST_USERNAME);

        $expectedMessage = [
            ['username' => self::TEST_USERNAME, 'tagname' => self::TEST_TAG1],
            ['username' => self::TEST_USERNAME, 'tagname' => self::TEST_TAG2]
        ];
        $this->publisher->expects(self::once())
            ->method('send')
            ->with('oro/data/update', json_encode($expectedMessage));

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->sender->send($tags);
    }

    public function testSendWithoutToken()
    {
        $this->tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn(null);

        $expectedMessage = [
            ['username' => null, 'tagname' => self::TEST_TAG1],
            ['username' => null, 'tagname' => self::TEST_TAG2]
        ];
        $this->publisher->expects(self::once())
            ->method('send')
            ->with('oro/data/update', json_encode($expectedMessage));

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->sender->send($tags);
    }

    public function testSendWhenTypeOfLoggedInUserIsNotSupported()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn(self::TEST_USERNAME);

        $expectedMessage = [
            ['username' => null, 'tagname' => self::TEST_TAG1],
            ['username' => null, 'tagname' => self::TEST_TAG2]
        ];
        $this->publisher->expects(self::once())
            ->method('send')
            ->with('oro/data/update', json_encode($expectedMessage));

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->sender->send($tags);
    }

    public function testSendWithoutTags()
    {
        $this->tokenStorage->expects(self::never())
            ->method('getToken');
        $this->publisher->expects(self::never())
            ->method('send');

        $this->sender->send([]);
    }

    public function testSendWithException()
    {
        $this->publisher->expects(self::once())
            ->method('send')
            ->willThrowException(new \Exception());
        $this->logger->expects(self::once())
            ->method('error');

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->sender->send($tags);
    }

    public function testSendToAllWithEmptyTags()
    {
        $this->publisher
            ->expects($this->never())
            ->method('send');

        $this->sender->send([]);
    }

    public function testSendToAllWhenExceptionIsThrown()
    {
        $tags = ['tag'];
        $exception = new \Exception('Exception message');
        $this->publisher
            ->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to publish a message to {topic}.', [
                'topic' => TopicSender::UPDATE_TOPIC,
                'exception' => $exception,
                'tags' => [['tagname' => 'tag', 'username' => null]]
            ]);

        $this->sender->send($tags);
    }

    public function testSendToAll()
    {
        $tags = ['tag'];
        $this->publisher
            ->expects($this->once())
            ->method('send')
            ->with(TopicSender::UPDATE_TOPIC, json_encode([['username' => null, 'tagname' => 'tag']]));

        $this->logger
            ->expects($this->never())
            ->method('error');

        $this->sender->send($tags);
    }
}
