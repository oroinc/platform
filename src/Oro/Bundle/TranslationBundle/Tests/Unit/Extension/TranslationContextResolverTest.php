<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Extension;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolver;

class TranslationContextResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationContextResolver */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->extension = new TranslationContextResolver();
    }

    public function testResolve()
    {
        $this->assertEquals('UI Label', $this->extension->resolve('Translation Key'));
    }
}
