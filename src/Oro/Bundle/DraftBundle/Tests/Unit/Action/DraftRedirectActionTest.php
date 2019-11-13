<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\DraftBundle\Action\DraftRedirectAction;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

class DraftRedirectActionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const ROUTE_NAME = 'route_name';
    private const REDIRECT_PATH = 'redirectUrl';

    /** @var DraftRedirectAction */
    private $action;

    /** @var ContextAccessor */
    private $contextAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RouterInterface */
    private $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->router
            ->expects($this->any())
            ->method('generate')
            ->willReturn(self::ROUTE_NAME);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->contextAccessor = new ContextAccessor();
        $this->action = new DraftRedirectAction(
            $this->contextAccessor,
            $this->configManager,
            $this->router,
            self::REDIRECT_PATH
        );
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @param array $options
     * @param $exceptionName
     *
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName): void
    {
        $this->expectException($exceptionName);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider(): array
    {
        return [
            'empty target' => [
                'options' => [
                    'route_parameters' => ['id' => 1]
                ],
                'exceptionName' => MissingOptionsException::class,
            ],
            'invalid target type' => [
                'options' => [
                    'target' => null,
                    'route_parameters' => ['id' => 1]
                ],
                'exceptionName' => InvalidOptionsException::class,
            ],
            'empty route_parameters' => [
                'options' => [
                    'target' => new PropertyPath('target'),
                ],
                'exceptionName' => MissingOptionsException::class,
            ],
            'invalid route_parameters type' => [
                'options' => [
                    'target' => new PropertyPath('target'),
                    'route_parameters' => null
                ],
                'exceptionName' => InvalidOptionsException::class,
            ],
            'empty id' => [
                'options' => [
                    'target' => new PropertyPath('target'),
                    'route_parameters' => []
                ],
                'exceptionName' => InvalidOptionsException::class,
            ],
        ];
    }

    public function testExecute(): void
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('getEntityMetadata')
            ->willReturn($this->getEntity(
                EntityMetadata::class,
                ['routes' => ['update' => self::ROUTE_NAME]],
                ['name' => DraftableEntityStub::class]
            ));

        $context = new ActionData(['target' => $this->getEntity(DraftableEntityStub::class, ['id' => 1])]);
        $this->action->initialize(['target' => new PropertyPath('target'), 'route_parameters' => ['id' => 1]]);
        $this->action->execute($context);

        $expectedUrlRedirect = $this->contextAccessor->getValue($context, new PropertyPath(self::REDIRECT_PATH));
        $this->assertEquals(self::ROUTE_NAME, $expectedUrlRedirect);
    }
}
