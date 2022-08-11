<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Condition;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Model\Condition\HasActiveIntegration;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class HasActiveIntegrationTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var HasActiveIntegration */
    private $condition;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->condition = new HasActiveIntegration($this->registry);
    }

    /**
     * @dataProvider failingOptionsDataProvider
     */
    public function testInitializeException(array $options)
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

    public function testInitialize()
    {
        $options = ['test'];
        $this->assertSame($this->condition, $this->condition->initialize($options));
    }

    public function testEvaluate()
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
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($repository);

        $this->assertTrue($this->condition->evaluate($context));
    }
}
