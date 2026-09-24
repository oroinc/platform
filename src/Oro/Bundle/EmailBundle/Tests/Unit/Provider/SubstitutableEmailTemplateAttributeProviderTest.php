<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\SubstitutableEmailTemplateAttributeProvider;
use Oro\Bundle\EmailBundle\Provider\SubstitutableEmailTemplateAttributesInterface;
use PHPUnit\Framework\TestCase;

final class SubstitutableEmailTemplateAttributeProviderTest extends TestCase
{
    public function testGetSubstitutionTemplateParameterReturnsNullWhenNothingIsDeclared(): void
    {
        $provider = new SubstitutableEmailTemplateAttributeProvider([]);

        self::assertNull($provider->getSubstitutionTemplateParameter(\ArrayObject::class, 'foo'));
        self::assertSame([], $provider->getAttributes());
    }

    public function testGetSubstitutionTemplateParameterMatchesDeclaredClassAndAttribute(): void
    {
        $declaration = $this->createMock(SubstitutableEmailTemplateAttributesInterface::class);
        $declaration
            ->method('getSubstitutableEmailTemplateAttributes')
            ->willReturn([\ArrayObject::class => ['foo' => 'fooParam', 'bar' => 'barParam']]);

        $provider = new SubstitutableEmailTemplateAttributeProvider([$declaration]);

        self::assertSame('fooParam', $provider->getSubstitutionTemplateParameter(\ArrayObject::class, 'foo'));
        self::assertSame('barParam', $provider->getSubstitutionTemplateParameter(\ArrayObject::class, 'bar'));
        self::assertNull($provider->getSubstitutionTemplateParameter(\ArrayObject::class, 'baz'));
        self::assertNull($provider->getSubstitutionTemplateParameter(\ArrayIterator::class, 'foo'));
    }

    public function testGetSubstitutionTemplateParameterCoversDescendantsOfTheDeclaredClass(): void
    {
        $declaration = $this->createMock(SubstitutableEmailTemplateAttributesInterface::class);
        $declaration
            ->method('getSubstitutableEmailTemplateAttributes')
            ->willReturn([\SplDoublyLinkedList::class => ['foo' => 'fooParam']]);

        $provider = new SubstitutableEmailTemplateAttributeProvider([$declaration]);

        self::assertSame('fooParam', $provider->getSubstitutionTemplateParameter(\SplStack::class, 'foo'));
    }

    public function testGetAttributesMergesDeclarationsOfTheSameClass(): void
    {
        $firstDeclaration = $this->createMock(SubstitutableEmailTemplateAttributesInterface::class);
        $firstDeclaration
            ->method('getSubstitutableEmailTemplateAttributes')
            ->willReturn([\ArrayObject::class => ['foo' => 'fooParam']]);

        $secondDeclaration = $this->createMock(SubstitutableEmailTemplateAttributesInterface::class);
        $secondDeclaration
            ->method('getSubstitutableEmailTemplateAttributes')
            ->willReturn([\ArrayObject::class => ['foo' => 'anotherFooParam', 'bar' => 'barParam']]);

        $thirdDeclaration = $this->createMock(SubstitutableEmailTemplateAttributesInterface::class);
        $thirdDeclaration
            ->method('getSubstitutableEmailTemplateAttributes')
            ->willReturn([\ArrayIterator::class => ['baz' => 'bazParam']]);

        $provider = new SubstitutableEmailTemplateAttributeProvider(
            [$firstDeclaration, $secondDeclaration, $thirdDeclaration]
        );

        self::assertSame(
            [
                \ArrayObject::class => ['foo' => 'fooParam', 'bar' => 'barParam'],
                \ArrayIterator::class => ['baz' => 'bazParam'],
            ],
            $provider->getAttributes()
        );
    }
}
