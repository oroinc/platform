<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Matcher;

use Oro\Bundle\DraftBundle\Duplicator\Matcher\GeneralMatcher;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class GeneralMatcherTest extends TestCase
{
    use EntityTrait;

    private GeneralMatcher $matcher;

    #[\Override]
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
