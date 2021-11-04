<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\ImportExport\Serializer\Normalizer\PrimaryItemCollectionNormalizer;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;

class PrimaryItemCollectionNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Serializer|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var PrimaryItemCollectionNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(Serializer::class);

        $this->normalizer = new PrimaryItemCollectionNormalizer();
        $this->normalizer->setSerializer($this->serializer);
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(object|string $data, bool $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationDataProvider(): array
    {
        $primaryItem = $this->createMock(PrimaryItemCollectionNormalizer::PRIMARY_ITEM_TYPE);

        return [
            ['stdClass', false],
            [new ArrayCollection(), false],
            [new ArrayCollection([$primaryItem, new \stdClass()]), false],
            [new ArrayCollection([$primaryItem]), true],
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(string $type, bool $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->normalizer->supportsDenormalization([], $type));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        $primaryItemClass = $this->getMockClass(PrimaryItemCollectionNormalizer::PRIMARY_ITEM_TYPE);

        return [
            ['stdClass', false],
            ['ArrayCollection', false],
            [ArrayCollection::class, false],
            ['Doctrine\\Common\\Collections\\ArrayCollection<Foo>', false],
            ["ArrayCollection<$primaryItemClass>", true],
            ["Doctrine\\Common\\Collections\\ArrayCollection<$primaryItemClass>", true],
        ];
    }

    public function testNormalize()
    {
        $format = null;
        $context = ['context'];

        $firstItem = $this->getMockPrimaryItem(false);
        $secondPrimaryItem = $this->getMockPrimaryItem(true);
        $thirdItem = $this->getMockPrimaryItem(false);

        $data = new ArrayCollection([$firstItem, $secondPrimaryItem, $thirdItem]);
        $this->serializer->expects($this->exactly(3))
            ->method('normalize')
            ->willReturnMap([
                [$firstItem, $format, $context, 'first'],
                [$secondPrimaryItem, $format, $context, 'second_primary'],
                [$thirdItem, $format, $context, 'third'],
            ]);

        $this->assertEquals(
            ['second_primary', 'first', 'third'],
            $this->normalizer->normalize($data, $format, $context)
        );
    }

    private function getMockPrimaryItem($primary)
    {
        $result = $this->createMock(PrimaryItemCollectionNormalizer::PRIMARY_ITEM_TYPE);
        $result->expects($this->once())
            ->method('isPrimary')
            ->willReturn($primary);

        return $result;
    }

    public function testDenormalizeWithItemType()
    {
        $format = null;
        $context = ['context'];

        $primaryItemClass = $this->getMockClass(PrimaryItemCollectionNormalizer::PRIMARY_ITEM_TYPE);

        $firstElement = $this->createMock(PrimaryItemCollectionNormalizer::PRIMARY_ITEM_TYPE);
        $firstElement->expects($this->once())
            ->method('setPrimary')
            ->with(true); // first is primary

        $secondElement = $this->createMock(PrimaryItemCollectionNormalizer::PRIMARY_ITEM_TYPE);
        $secondElement->expects($this->once())
            ->method('setPrimary')
            ->with(false);

        $thirdElement = $this->createMock(PrimaryItemCollectionNormalizer::PRIMARY_ITEM_TYPE);
        $thirdElement->expects($this->once())
            ->method('setPrimary')
            ->with(false);

        $this->serializer->expects($this->exactly(3))
            ->method('denormalize')
            ->willReturnMap([
                ['first', $primaryItemClass, $format, $context, $firstElement],
                ['second', $primaryItemClass, $format, $context, $secondElement],
                ['third', $primaryItemClass, $format, $context, $thirdElement],
            ]);

        $this->assertEquals(
            new ArrayCollection([$firstElement, $secondElement, $thirdElement]),
            $this->normalizer->denormalize(
                ['first', 'second', 'third'],
                "ArrayCollection<$primaryItemClass>",
                $format,
                $context
            )
        );
    }
}
