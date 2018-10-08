<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolverInterface;
use Oro\Bundle\TranslationBundle\Provider\TranslationContextProvider;

class TranslationContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationContextProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->provider = new TranslationContextProvider();
    }

    public function testResolveContextAndNoExtensions()
    {
        $this->assertNull($this->provider->resolveContext('Translation Key'));
    }

    public function testResolveContext()
    {
        $extension1 = $this->createMock(TranslationContextResolverInterface::class);
        $extension2 = $this->createMock(TranslationContextResolverInterface::class);
        $extension3 = $this->createMock(TranslationContextResolverInterface::class);

        $extension1->expects($this->once())->method('resolve')->willReturn(null);
        $extension2->expects($this->once())->method('resolve')->willReturn('Resolved Value');
        $extension3->expects($this->never())->method('resolve');

        $this->provider->addExtension($extension1, 'e1');
        $this->provider->addExtension($extension2, 'e2');
        $this->provider->addExtension($extension3, 'e3');

        $this->assertEquals('Resolved Value', $this->provider->resolveContext('Translation Key'));
    }
}
