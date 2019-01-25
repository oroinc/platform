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

    protected function createValidator()
    {
        return new MimeTypeValidator(self::ALLOWED_FILE_MIME_TYPES, self::ALLOWED_IMAGE_MIME_TYPES);
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new MimeType();

        return parent::createContext();
    }

    public function testConfiguration()
    {
        $this->assertEquals(MimeTypeValidator::class, $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testValidMimeTypesForPartialListOfMimeTypes()
    {
        $this->validator->validate(['application/vnd.ms-excel', 'image/png'], $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidMimeTypesForFullListOfMimeTypes()
    {
        $this->validator->validate(self::ALLOWED_FILE_MIME_TYPES, $this->constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testInvalidMimeType($value, $notAllowedMimeTypes, $plural)
    {
        $this->validator->validate($value, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->setParameters(['{{ notAllowedMimeTypes }}' => $notAllowedMimeTypes])
            ->setPlural($plural)
            ->assertRaised();
    }

    public function invalidValuesDataProvider()
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
