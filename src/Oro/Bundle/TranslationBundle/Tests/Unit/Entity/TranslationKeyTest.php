<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Entity;

use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class TranslationKeyTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(new TranslationKey(), [
            ['id', 1],
            ['key', 'test_key'],
            ['domain', 'test_domain'],
        ]);
    }
}
