<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Entity;

use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class TranslationKeyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new TranslationKey(), [
            ['id', 1],
            ['key', 'test_key'],
            ['domain', 'test_domain'],
        ]);
    }
}
