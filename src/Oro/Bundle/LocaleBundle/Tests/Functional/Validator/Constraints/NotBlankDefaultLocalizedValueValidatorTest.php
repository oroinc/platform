<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Validator\Constraints\NotBlankDefaultLocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NotBlankDefaultLocalizedValueValidatorTest extends WebTestCase
{
    /** @var ValidatorInterface */
    private $validator;

    protected function setUp(): void
    {
        $this->initClient();

        $this->validator = self::getContainer()->get('validator');
    }

    public function testValidatorDoesNotAddValidationErrorIfLocalizedFallbackCollectionHasDefaultLocalizedValue()
    {
        $constraint = new NotBlankDefaultLocalizedFallbackValue();

        $expectedPropertyName = 'titles';

        $entity = new Localization();
        $defaultFallback = new LocalizedFallbackValue();
        $defaultFallback->setString('Test');
        /**
         * Only fallback without localization will be default fallback
         * @see \Oro\Bundle\LocaleBundle\Entity\FallbackTrait::getDefaultFallbackValue
         */
        $defaultFallback->setLocalization(null);
        $entity->addTitle($defaultFallback);

        $this->clearEntityMetadataConstraints($entity);
        $this->addPropertyValidationConstraint($entity, $expectedPropertyName, $constraint);

        $result = $this->validator->validate($entity);

        self::assertEquals(0, $result->count());
    }

    public function testValidateAddAnErrorToTheContextIfLocalizedFallbackCollectionDoesNotContainDefaultValue()
    {
        $constraint = new NotBlankDefaultLocalizedFallbackValue();

        $expectedPropertyName = 'titles';
        $expectedMessage = 'Default localized value is blank';

        $entity = new Localization();

        $specificFallback = new LocalizedFallbackValue();
        $specificFallback->setLocalization(new Localization());
        $entity->addTitle($specificFallback);

        $expectedInvalidValue = $entity->getTitles();
        self::assertCount(1, $expectedInvalidValue, 'Precondition failed title was not set');

        $this->clearEntityMetadataConstraints($entity);
        $this->addPropertyValidationConstraint($entity, $expectedPropertyName, $constraint);

        $result = $this->validator->validate($entity);

        self::assertCount(1, $result);

        /** @var ConstraintViolation $actualConstraintViolation */
        $actualConstraintViolation = $result[0];

        self::assertEquals($constraint, $actualConstraintViolation->getConstraint());
        self::assertEquals($expectedMessage, $actualConstraintViolation->getMessage());
        self::assertEquals($expectedPropertyName, $actualConstraintViolation->getPropertyPath());
        self::assertEquals($expectedInvalidValue, $actualConstraintViolation->getInvalidValue());
    }

    public function testValidateAddAnErrorToTheContextIfLocalizedFallbackCollectionIsEmpty()
    {
        $constraint = new NotBlankDefaultLocalizedFallbackValue();

        $expectedPropertyName = 'titles';
        $expectedMessage = 'Default localized value is blank';

        $entity = new Localization();
        $expectedInvalidValue = $entity->getTitles();

        $this->clearEntityMetadataConstraints($entity);
        $this->addPropertyValidationConstraint($entity, $expectedPropertyName, $constraint);

        $result = $this->validator->validate($entity);

        self::assertCount(1, $result);

        /** @var ConstraintViolation $actualConstraintViolation */
        $actualConstraintViolation = $result[0];

        self::assertEquals($constraint, $actualConstraintViolation->getConstraint());
        self::assertEquals($expectedMessage, $actualConstraintViolation->getMessage());
        self::assertEquals($expectedPropertyName, $actualConstraintViolation->getPropertyPath());
        self::assertEquals($expectedInvalidValue, $actualConstraintViolation->getInvalidValue());
    }

    /**
     * Helps to clear unnecessary validation constraints to be able to check only specific constraint work
     */
    private function clearEntityMetadataConstraints(object $entity): void
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor($entity);

        $metadata->constraints = [];
        $metadata->constraintsByGroup = [];

        foreach ($metadata->getConstrainedProperties() as $propertyName) {
            $propertyMetadata = $metadata->properties[$propertyName];
            $propertyMetadata->constraints = [];
            $propertyMetadata->constraintsByGroup = [];
        }
    }

    private function addPropertyValidationConstraint(
        object $entity,
        string $propertyName,
        Constraint $constraint
    ): void {
        /** @var ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor($entity);
        $metadata->addPropertyConstraint($propertyName, $constraint);
    }
}
