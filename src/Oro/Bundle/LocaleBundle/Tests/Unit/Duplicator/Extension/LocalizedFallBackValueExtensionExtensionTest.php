<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Duplicator\Extension;

use DeepCopy\Filter\Doctrine\DoctrineCollectionFilter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Duplicator\DraftContext;
use Oro\Bundle\DraftBundle\Duplicator\Matcher\PropertiesNameMatcher;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\LocaleBundle\Duplicator\Extension\LocalizedFallBackValueExtension;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTrait;

class LocalizedFallBackValueExtensionExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LocalizedFallBackValueExtension */
    private $extension;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $classMetaData = $this->getEntity(
            ClassMetadataInfo::class,
            [
                'associationMappings' => [
                    'field1' => [
                        'targetEntity' => LocalizedFallbackValue::class
                    ]
                ]
            ],
            [
                $this->getEntity(DraftableEntityStub::class)
            ]
        );
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetaData);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry
            ->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        $this->extension = new LocalizedFallBackValueExtension($this->registry);
    }

    public function testGetFilter(): void
    {
        $this->assertEquals(new DoctrineCollectionFilter(), $this->extension->getFilter());
    }

    public function testGetMatcher(): void
    {
        $context = new DraftContext();
        $context->offsetSet('source', $this->getEntity(DraftableEntityStub::class));
        $this->extension->setContext($context);
        $this->assertEquals(new PropertiesNameMatcher(['field1']), $this->extension->getMatcher());
    }

    public function testIsSupport(): void
    {
        /** @var DraftableInterface $source */
        $source = $this->getEntity(DraftableEntityStub::class);
        $this->assertTrue($this->extension->isSupport($source));
    }
}
