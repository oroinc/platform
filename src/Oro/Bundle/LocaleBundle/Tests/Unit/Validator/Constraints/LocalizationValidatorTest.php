<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Entity;
use Oro\Bundle\LocaleBundle\Validator\Constraints;
use Oro\Bundle\LocaleBundle\Validator\Constraints\LocalizationValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LocalizationValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): LocalizationValidator
    {
        return new LocalizationValidator();
    }

    public function testGetTargets()
    {
        $constraint = new Constraints\Localization();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateWithoutCircularReference()
    {
        $localization1 = $this->createLocalization('loca1', 1);
        $localization2 = $this->createLocalization('loca2', 2);
        $localization1->setParentLocalization($localization2);

        $constraint = new Constraints\Localization();
        $this->validator->validate($localization1, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithCircularReference()
    {
        $localization1 = $this->createLocalization('loca1', 1);
        $localization2 = $this->createLocalization('loca2', 2);
        $localization3 = $this->createLocalization('loca3', 3);

        $localization1->setParentLocalization($localization2);
        $localization1->addChildLocalization($localization3);

        $localization2->setParentLocalization($localization3);
        $localization2->addChildLocalization($localization1);

        $localization3->setParentLocalization($localization3);
        $localization3->addChildLocalization($localization2);

        $constraint = new Constraints\Localization();
        $this->validator->validate($localization3, $constraint);

        $this->buildViolation($constraint->messageCircularReference)
            ->atPath('property.path.parentLocalization')
            ->assertRaised();
    }

    public function testValidateSelfParent()
    {
        $localization1 = $this->createLocalization('loca1', 1);
        $localization1->setParentLocalization($localization1);

        $constraint = new Constraints\Localization();
        $this->validator->validate($localization1, $constraint);

        $this->buildViolation($constraint->messageCircularReference)
            ->atPath('property.path.parentLocalization')
            ->assertRaised();
    }

    public function testUnexpectedValue()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\LocaleBundle\Entity\Localization", "string" given'
        );

        $constraint = new Constraints\Localization();
        $this->validator->validate('test', $constraint);
    }

    public function testUnexpectedClass()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\LocaleBundle\Entity\Localization", "stdClass" given'
        );

        $constraint = new Constraints\Localization();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    private function createLocalization(string $name, int $id): Entity\Localization
    {
        $localization = new Entity\Localization();
        $localization->setName($name);
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }
}
