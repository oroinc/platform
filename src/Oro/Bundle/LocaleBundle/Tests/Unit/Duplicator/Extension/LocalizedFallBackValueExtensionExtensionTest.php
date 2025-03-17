<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Duplicator\Extension;

use DeepCopy\Filter\Doctrine\DoctrineCollectionFilter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Duplicator\DraftContext;
use Oro\Bundle\DraftBundle\Duplicator\Matcher\PropertiesNameMatcher;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\LocaleBundle\Duplicator\Extension\LocalizedFallBackValueExtension;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use PHPUnit\Framework\TestCase;

class LocalizedFallBackValueExtensionExtensionTest extends TestCase
{
    private LocalizedFallBackValueExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $classMetaData = new ClassMetadataInfo(DraftableEntityStub::class);
        $classMetaData->associationMappings = [
            'field1' => [
                'targetEntity' => LocalizedFallbackValue::class
            ]
        ];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetaData);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        $this->extension = new LocalizedFallBackValueExtension($doctrine);
    }

    public function testGetFilter(): void
    {
        $this->assertEquals(new DoctrineCollectionFilter(), $this->extension->getFilter());
    }

    public function testGetMatcher(): void
    {
        $context = new DraftContext();
        $context->offsetSet('source', new DraftableEntityStub());
        $this->extension->setContext($context);
        $this->assertEquals(new PropertiesNameMatcher(['field1']), $this->extension->getMatcher());
    }

    public function testIsSupport(): void
    {
        $source = new DraftableEntityStub();
        $this->assertTrue($this->extension->isSupport($source));
    }
}
