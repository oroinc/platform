<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Matcher;

use Oro\Bundle\DraftBundle\Duplicator\Matcher\GeneralMatcher;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Component\Testing\Unit\EntityTrait;

class GeneralMatcherTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var GeneralMatcher */
    private $matcher;

    protected function setUp(): void
    {
        $this->matcher = new GeneralMatcher();
    }

    /**
     * @dataProvider typeDataProvider
     */
    public function testMatches(mixed $value): void
    {
        $matches = $this->matcher->matches($value, '');
        $this->assertTrue($matches);
    }

    public function typeDataProvider(): array
    {
        return [
            'string' => ['string'],
            'boolean' => ['integer'],
            'integer' => [1],
            'array' => [[]],
            'object' => [new DraftableEntityStub()],
            'null' => [null],
        ];
    }
}
