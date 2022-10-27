<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Resolver;

use Oro\Bundle\ActionBundle\Model\OptionsAssembler;
use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use Oro\Component\ConfigExpression\ContextAccessor;

class OptionsResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var OptionsAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsAssembler;

    /** @var OptionsResolver */
    private $optionsResolver;

    protected function setUp(): void
    {
        $this->optionsAssembler = $this->createMock(OptionsAssembler::class);
        $this->contextAccessor = $this->createMock(ContextAccessor::class);

        $this->optionsResolver = new OptionsResolver($this->optionsAssembler, $this->contextAccessor);
    }

    /**
     * @dataProvider resolveOptionsDataProvider
     */
    public function testResolveOptions(array $options, array $expected)
    {
        $data = ['some' => 'data'];
        $this->optionsAssembler->expects($this->once())
            ->method('assemble')
            ->willReturnArgument(0);
        $this->contextAccessor->expects($this->exactly($options ? 1 : 0))
            ->method('getValue')
            ->willReturnCallback(function ($data, $value) {
                return $value . '_resolved';
            });

        $this->assertEquals($expected, $this->optionsResolver->resolveOptions($data, $options));
    }

    public function resolveOptionsDataProvider(): array
    {
        return [
            'empty' => [
                'options' => [],
                'expected' => [],
            ],
            'simple array' => [
                'options' => ['some' => 'option'],
                'expected' => ['some' => 'option_resolved'],
            ],
            'complex array' => [
                'options' => ['some' => ['array' => 'option']],
                'expected' => ['some' => ['array' => 'option_resolved']],
            ],
        ];
    }
}
