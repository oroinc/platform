<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Condition;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Model\Condition\HasActiveIntegration;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HasActiveIntegrationTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private HasActiveIntegration $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->condition = new HasActiveIntegration($this->registry);
    }

    /**
     * @dataProvider failingOptionsDataProvider
     */
    public function testInitializeException(array $options): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->condition->initialize($options);
    }

    public function failingOptionsDataProvider(): array
    {
        return [
            'empty' => [[]],
            'accordion option set' => [['\\', '//']]
        ];
    }

    public function testInitialize(): void
    {
        $options = ['test'];
        $this->assertSame($this->condition, $this->condition->initialize($options));
    }

    public function testEvaluate(): void
    {
        $context = [];
        $type = 'testType';
        $entity = new \stdClass();

        $this->condition->initialize([$type]);

        $repository = $this->createMock(ChannelRepository::class);
        $repository->expects($this->once())
            ->method('getConfiguredChannelsForSync')
            ->with($type, true)
            ->willReturn([$entity]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Channel::class)
            ->willReturn($repository);

        $this->assertTrue($this->condition->evaluate($context));
    }
}
