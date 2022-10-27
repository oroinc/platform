<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Oro\Bundle\LayoutBundle\Layout\Serializer\OptionValueBagNormalizer;
use Oro\Component\Layout\OptionValueBag;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class OptionValueBagNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OptionValueBagNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new OptionValueBagNormalizer();
        $serializer = new Serializer([$this->normalizer], [new JsonEncoder()]);
        $this->normalizer->setSerializer($serializer);
    }

    public function testSupportsNormalization()
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));

        $this->assertTrue($this->normalizer->supportsNormalization(
            $this->createMock(OptionValueBag::class)
        ));
    }

    public function testSupportsDenormalization()
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], \stdClass::class));
        $this->assertTrue($this->normalizer->supportsDenormalization([], OptionValueBag::class));
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testNormalizeDenormalize(OptionValueBag $bag)
    {
        $normalized = $this->normalizer->normalize($bag);
        $denormalized = $this->normalizer->denormalize($normalized, OptionValueBag::class);

        $this->assertEquals($bag, $denormalized);
    }

    public function optionsDataProvider(): array
    {
        return [
            'empty bag' => [
                'actual' => $this->createOptionValueBag([]),
            ],
            'string arguments' => [
                'actual' => $this->createOptionValueBag([
                        ['method' => 'add', 'arguments' => ['first']],
                        ['method' => 'add', 'arguments' => ['second']],
                        ['method' => 'replace', 'arguments' => ['first', 'result']],
                        ['method' => 'remove', 'arguments' => ['second']],
                    ]),
            ],
            'array arguments' => [
                'actual' => $this->createOptionValueBag([
                        ['method' => 'add', 'arguments' => [['one', 'two', 'three']]],
                        ['method' => 'remove', 'arguments' => [['one', 'three']]],
                    ]),
            ],
            'arguments with recursion object' => [
                'actual' => $this->createOptionValueBag([
                    ['method' => 'add',    'arguments' => [['one', 'two', 'three']]],
                    ['method' => 'remove', 'arguments' => [
                        $this->createOptionValueBag([
                            ['method' => 'add', 'arguments' => ['first']]
                        ])
                    ]],
                ]),
            ],
            'arguments with recursion array' => [
                'actual' => $this->createOptionValueBag([
                    ['method' => 'add', 'arguments' => [['one', 'two', 'three']]],
                    ['method' => 'remove', 'arguments' => [[
                        $this->createOptionValueBag([
                            ['method' => 'add',    'arguments' => ['first']],
                            ['method' => 'remove', 'arguments' => ['two']]
                        ]),
                        $this->createOptionValueBag([
                            ['method' => 'add', 'arguments' => ['third']],
                        ])
                    ]]],
                ]),
            ],
        ];
    }

    private function createOptionValueBag(array $actions): OptionValueBag
    {
        $bag = new OptionValueBag();
        foreach ($actions as $action) {
            call_user_func_array([$bag, $action['method']], $action['arguments']);
        }

        return $bag;
    }
}
