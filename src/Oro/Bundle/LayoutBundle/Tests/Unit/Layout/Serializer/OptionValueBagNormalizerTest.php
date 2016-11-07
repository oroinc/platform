<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\Layout\Serializer\OptionValueBagNormalizer;

class OptionValueBagNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OptionValueBagNormalizer */
    protected $normalizer;

    protected function setUp()
    {
        $this->normalizer = new OptionValueBagNormalizer();
    }

    public function testSupportsNormalization()
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));

        $this->assertTrue($this->normalizer->supportsNormalization(
            $this->getMock(OptionValueBag::class, [], [], '', false)
        ));
    }

    public function testSupportsDenormalization()
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], new \stdClass()));
        $this->assertTrue($this->normalizer->supportsDenormalization([], OptionValueBag::class));
    }

    /**
     * @param OptionValueBag $bag
     * @dataProvider optionsDataProvider
     */
    public function testNormalizeDenormalize(OptionValueBag $bag)
    {
        $normalized = $this->normalizer->normalize($bag);
        $denormalized = $this->normalizer->denormalize($normalized, OptionValueBag::class);

        $this->assertEquals($bag, $denormalized);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
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
        ];
    }

    /**
     * @param array $actions
     * @return OptionValueBag
     */
    protected function createOptionValueBag(array $actions)
    {
        $bag = new OptionValueBag();
        foreach ($actions as $action) {
            call_user_func_array([$bag, $action['method']], $action['arguments']);
        }

        return $bag;
    }
}
