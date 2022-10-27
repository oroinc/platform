<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\DraftBundle\Action\DraftRedirectAction;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;

class DraftRedirectActionTest extends \PHPUnit\Framework\TestCase
{
    private const ROUTE_NAME = 'route_name';

    /** @var ContextAccessor */
    private $contextAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var DraftRedirectAction */
    private $action;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->contextAccessor = new ContextAccessor();

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->any())
            ->method('generate')
            ->willReturn(self::ROUTE_NAME);

        $this->action = new DraftRedirectAction(
            $this->contextAccessor,
            $this->configManager,
            $router
        );
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName): void
    {
        $this->expectException($exceptionName);
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            'empty options' => [
                'options' => [],
                'exceptionName' => MissingOptionsException::class,
            ],
            'empty source' => [
                'options' => [
                    'route' => new PropertyPath('route'),
                ],
                'exceptionName' => MissingOptionsException::class,
            ],
            'invalid source type' => [
                'options' => [
                    'source' => null,
                    'route' => new PropertyPath('route'),
                ],
                'exceptionName' => InvalidOptionsException::class,
            ],
            'empty route' => [
                'options' => [
                    'source' => new PropertyPath('source'),
                ],
                'exceptionName' => MissingOptionsException::class,
            ],
            'invalid route type' => [
                'options' => [
                    'source' => new PropertyPath('source'),
                    'route' => null,
                ],
                'exceptionName' => InvalidOptionsException::class,
            ]
        ];
    }

    public function testExecute(): void
    {
        $entityMetadata = new EntityMetadata(DraftableEntityStub::class);
        $entityMetadata->routes = ['update' => self::ROUTE_NAME];

        $draftableEntity = new DraftableEntityStub();
        ReflectionUtil::setId($draftableEntity, 1);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn($entityMetadata);

        $context = new ActionData(['source' => $draftableEntity]);
        $this->action->initialize(['source' => new PropertyPath('source'), 'route' => new PropertyPath('route')]);
        $this->action->execute($context);

        $expectedUrlRedirect = $this->contextAccessor->getValue($context, new PropertyPath('redirectUrl'));
        $this->assertEquals(self::ROUTE_NAME, $expectedUrlRedirect);
    }
}
