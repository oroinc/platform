<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Validator\Constraints\MimeType;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\MimeTypeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MimeTypeValidatorTest extends ConstraintValidatorTestCase
{
    private const ALLOWED_FILE_MIME_TYPES = [
        'application/vnd.ms-excel',
        'application/pdf',
        'image/png'
    ];

    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/gif',
        'image/png'
    ];

    protected function createValidator(): MimeTypeValidator
    {
        return new MimeTypeValidator(self::ALLOWED_FILE_MIME_TYPES, self::ALLOWED_IMAGE_MIME_TYPES);
    }

    public function testGetTargets()
    {
        $constraint = new MimeType();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidMimeTypesForPartialListOfMimeTypes()
    {
        $constraint = new MimeType();
        $this->validator->validate(['application/vnd.ms-excel', 'image/png'], $constraint);
        $this->assertNoViolation();
    }

    public function testValidMimeTypesForFullListOfMimeTypes()
    {
        $constraint = new MimeType();
        $this->validator->validate(self::ALLOWED_FILE_MIME_TYPES, $constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testInvalidMimeType(array|string $value, string $notAllowedMimeTypes, int $plural)
    {
        $constraint = new MimeType();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ notAllowedMimeTypes }}' => $notAllowedMimeTypes])
            ->setPlural($plural)
            ->assertRaised();
    }

    public function invalidValuesDataProvider(): array
    {
        return [
            'invalid one MIME type'             => [
                ['application/pdf', 'application/not_allowed'],
                'application/not_allowed',
                1
            ],
            'invalid several MIME type'         => [
                ['application/pdf', 'application/not_allowed1', 'application/not_allowed2'],
                'application/not_allowed1, application/not_allowed2',
                2
            ],
            'invalid one MIME type, string'     => [
                "application/pdf\napplication/not_allowed",
                'application/not_allowed',
                1
            ],
            'invalid several MIME type, string' => [
                "application/pdf\napplication/not_allowed1\napplication/not_allowed2",
                'application/not_allowed1, application/not_allowed2',
                2
            ],
            'string, CRLF delimiter'            => [
                "application/pdf\r\napplication/not_allowed1\r\napplication/not_allowed2",
                'application/not_allowed1, application/not_allowed2',
                2
            ],
        ];
    }
}
