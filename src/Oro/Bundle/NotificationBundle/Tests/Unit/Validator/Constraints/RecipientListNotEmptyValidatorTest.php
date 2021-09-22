<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Validator\Constraints\RecipientListNotEmpty;
use Oro\Bundle\NotificationBundle\Validator\Constraints\RecipientListNotEmptyValidator;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RecipientListNotEmptyValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): RecipientListNotEmptyValidator
    {
        return new RecipientListNotEmptyValidator();
    }

    /**
     * @dataProvider validateWhenValidDataProvider
     */
    public function testValidateWhenValid(RecipientList $value): void
    {
        $constraint = new RecipientListNotEmpty();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function validateWhenValidDataProvider(): array
    {
        return [
            ['value' => (new RecipientList())->setEmail('sample_email')],
            ['value' => (new RecipientList())->addGroup(new Group())],
            ['value' => (new RecipientList())->addUser(new User())],
            ['value' => (new RecipientList())->setEntityEmails(['entity_emails'])],
            ['value' => (new RecipientList())->setAdditionalEmailAssociations(['extra_association'])],
        ];
    }

    public function testValidateWhenNotValid(): void
    {
        $constraint = new RecipientListNotEmpty();
        $this->validator->validate(new RecipientList(), $constraint);

        $this
            ->buildViolation($constraint->message)
            ->assertRaised();
    }
}
