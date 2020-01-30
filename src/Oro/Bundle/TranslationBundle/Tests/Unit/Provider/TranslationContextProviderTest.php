<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolverInterface;
use Oro\Bundle\TranslationBundle\Provider\TranslationContextProvider;

class TranslationContextProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testResolveContextAndNoExtensions()
    {
        $provider = new TranslationContextProvider([]);
        $this->assertNull($provider->resolveContext('Translation Key'));
    }

    public function testResolveContext()
    {
        $extension1 = $this->createMock(TranslationContextResolverInterface::class);
        $extension2 = $this->createMock(TranslationContextResolverInterface::class);
        $extension3 = $this->createMock(TranslationContextResolverInterface::class);

        $extension1->expects($this->once())
            ->method('resolve')
            ->willReturn(null);
        $extension2->expects($this->once())
            ->method('resolve')
            ->willReturn('Resolved Value');
        $extension3->expects($this->never())
            ->method('resolve');

        $provider = new TranslationContextProvider([
            $extension1,
            $extension2,
            $extension3
        ]);

        $this->assertEquals('Resolved Value', $provider->resolveContext('Translation Key'));
        // test that th resolved content is cached
        $this->assertEquals('Resolved Value', $provider->resolveContext('Translation Key'));
    }

    public function testReset()
    {
        $extension1 = $this->createMock(TranslationContextResolverInterface::class);
        $extension1->expects($this->exactly(2))
            ->method('resolve')
            ->willReturn('Resolved Value');

        $provider = new TranslationContextProvider([$extension1]);

        $this->assertEquals('Resolved Value', $provider->resolveContext('Translation Key'));
        $provider->reset();
        $this->assertEquals('Resolved Value', $provider->resolveContext('Translation Key'));
    }
}
