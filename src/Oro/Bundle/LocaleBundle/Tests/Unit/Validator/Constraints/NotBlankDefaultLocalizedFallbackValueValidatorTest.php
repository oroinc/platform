<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Validator\Constraints\NotBlankDefaultLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Validator\Constraints\NotBlankDefaultLocalizedFallbackValueValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotBlankDefaultLocalizedFallbackValueValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var LocalizationHelper|MockObject
     */
    protected $localizationHelper;

    protected function createValidator()
    {
        return new NotBlankDefaultLocalizedFallbackValueValidator();
    }

    /**
     * @dataProvider validLocalizationDataProvider
     *
     * @param string|int $defaultTitleValue
     */
    public function testValidDefaultLocalizedValue($defaultTitleValue)
    {
        $defaultValue = new LocalizedFallbackValue();
        $defaultValue->setString($defaultTitleValue);
        $values = new ArrayCollection([$defaultValue]);

        $this->validator->validate($values, new NotBlankDefaultLocalizedFallbackValue());

        $this->assertNoViolation();
    }

    public function validLocalizationDataProvider(): array
    {
        return [
            [0],
            ['0'],
            ['title'],
        ];
    }

    /**
     * @dataProvider notValidLocalizationDataProvider
     *
     * @param string|int $defaultTitleValue
     */
    public function testNotValidDefaultLocalizedValue($defaultTitleValue)
    {
        $defaultValue = new LocalizedFallbackValue();
        $defaultValue->setString($defaultTitleValue);
        $values = new ArrayCollection([$defaultValue]);

        $this->validator->validate($values, new NotBlankDefaultLocalizedFallbackValue());

        $this->buildViolation('oro.locale.validators.not_blank_default_localized_value.error_message')
            ->assertRaised();
    }

    public function notValidLocalizationDataProvider(): array
    {
        return [
            [''],
            ['  '],
            [null],
        ];
    }

    public function testEmptyDefaultLocalizedValue()
    {
        $value = new LocalizedFallbackValue();
        $value->setString('0');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection(
            [
                $value
            ]
        );

        $this->validator->validate($values, new NotBlankDefaultLocalizedFallbackValue());

        $this->buildViolation('oro.locale.validators.not_blank_default_localized_value.error_message')
            ->assertRaised();
    }

    public function testUnexpectedValueException()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(new \stdClass(), new NotBlankDefaultLocalizedFallbackValue());
    }
}
