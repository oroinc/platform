<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Formatter\LocalizedFallbackValueFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocalizedFallbackValueFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var PropertyAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyAccessor;

    /** @var LocalizedFallbackValueFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->formatter = new LocalizedFallbackValueFormatter(
            $this->doctrineHelper,
            $this->localizationHelper,
            $this->translator,
            $this->propertyAccessor
        );
    }

    public function testFormatWithEmptyAssociationName(): void
    {
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('N/A')
            ->willReturnArgument(0);

        $this->assertEquals('N/A', $this->formatter->format('translated_value'));
    }

    public function testFormatWithInvalidAssociationName(): void
    {
        $localization = new Localization();
        $localization->addTitle((new LocalizedFallbackValue())->setString('Fallback value'));

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('N/A')
            ->willReturnArgument(0);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata
            ->expects($this->once())
            ->method('hasAssociation')
            ->with('titles')
            ->willReturn(false);
        $classMetadata
            ->expects($this->never())
            ->method('getAssociationMapping');

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn($classMetadata);

        $this->assertEquals('N/A', $this->formatter->format($localization, ['associationName' => 'titles']));
    }

    public function testFormatWithValidAssociationName(): void
    {
        $localization = new Localization();
        $localization->addTitle((new LocalizedFallbackValue())->setString('Fallback value'));

        $associationMapping = [
            'type' => ClassMetadata::TO_MANY,
            'targetEntity' => LocalizedFallbackValue::class
        ];

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata
            ->expects($this->once())
            ->method('hasAssociation')
            ->with('titles')
            ->willReturn(true);
        $classMetadata
            ->expects($this->once())
            ->method('getAssociationMapping')
            ->with('titles')
            ->willReturn($associationMapping);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn($classMetadata);

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizedValue')
            ->with($localization->getTitles())
            ->willReturn('Fallback value');

        $this->assertEquals('Fallback value', $this->formatter->format($localization, ['associationName' => 'titles']));
    }

    public function testGetDefaultValue(): void
    {
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('N/A')
            ->willReturnArgument(0);

        $this->assertEquals('N/A', $this->formatter->getDefaultValue());
    }
}
