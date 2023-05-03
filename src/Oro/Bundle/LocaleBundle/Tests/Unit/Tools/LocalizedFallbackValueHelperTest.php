<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\CustomLocalizedFallbackValueStub;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\LocaleBundle\Tools\LocalizedFallbackValueHelper;

class LocalizedFallbackValueHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testCloneCollectionWhenInvalidClass(): void
    {
        $this->expectExceptionObject(
            new \LogicException(
                sprintf(
                    'The argument "$entityClass" is expected to be a heir of "%s"',
                    AbstractLocalizedFallbackValue::class
                )
            )
        );

        LocalizedFallbackValueHelper::cloneCollection(new ArrayCollection([new \stdClass()]), \stdClass::class);
    }

    /**
     * @dataProvider cloneCollectionDataProvider
     */
    public function testCloneCollection(Collection $collection, ?string $entityClass, Collection $expected): void
    {
        self::assertEquals($expected, LocalizedFallbackValueHelper::cloneCollection($collection, $entityClass));
    }

    public function cloneCollectionDataProvider(): array
    {
        return [
            'empty collection' => [
                'collection' => new ArrayCollection(),
                'entityClass' => null,
                'expected' => new ArrayCollection()
            ],
            'without $entityClass' => [
                'collection' => new ArrayCollection(
                    [
                        (new CustomLocalizedFallbackValueStub(11))->setString('sample string1'),
                        (new CustomLocalizedFallbackValueStub(22))
                            ->setFallback(FallbackType::SYSTEM)
                            ->setLocalization(new LocalizationStub(42)),
                    ]
                ),
                'entityClass' => null,
                'expected' => new ArrayCollection([
                    (new CustomLocalizedFallbackValueStub())->setString('sample string1'),
                    (new CustomLocalizedFallbackValueStub())
                        ->setFallback(FallbackType::SYSTEM)
                        ->setLocalization(new LocalizationStub(42)),
                ])
            ],
            'with $entityClass' => [
                'collection' => new ArrayCollection(
                    [
                        (new CustomLocalizedFallbackValueStub(11))->setString('sample string1'),
                        (new CustomLocalizedFallbackValueStub(22))
                            ->setFallback(FallbackType::SYSTEM)
                            ->setLocalization(new LocalizationStub(42)),
                    ]
                ),
                'entityClass' => LocalizedFallbackValue::class,
                'expected' => new ArrayCollection([
                    (new LocalizedFallbackValue())->setString('sample string1'),
                    (new LocalizedFallbackValue())
                        ->setFallback(FallbackType::SYSTEM)
                        ->setLocalization(new LocalizationStub(42)),
                ])
            ]
        ];
    }
}
