<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Validator\Constraints\MimeType;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\MimeTypeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class MimeTypeValidatorTest extends \PHPUnit\Framework\TestCase
{
    const ALLOWED_FILE_MIME_TYPES = [
        'application/vnd.ms-excel',
        'application/pdf',
        'image/png'
    ];

    const ALLOWED_IMAGE_MIME_TYPES = [
        'image/gif',
        'image/png'
    ];

    /** @var MimeTypeValidator */
    protected $validator;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var MimeType */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new MimeType();
        $this->validator = new MimeTypeValidator(self::ALLOWED_FILE_MIME_TYPES, self::ALLOWED_IMAGE_MIME_TYPES);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(MimeTypeValidator::class, $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testValidMimeTypesForPartialListOfMimeTypes()
    {
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(['application/vnd.ms-excel', 'image/png'], $this->constraint);
    }

    public function testValidMimeTypesForFullListOfMimeTypes()
    {
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(self::ALLOWED_FILE_MIME_TYPES, $this->constraint);
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testInvalidMimeType($value, $notAllowedMimeTypes, $plural)
    {
        $violation = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($violation);
        $violation->expects($this->once())
            ->method('setParameters')
            ->with(['{{ notAllowedMimeTypes }}' => $notAllowedMimeTypes])
            ->willReturnSelf();
        $violation->expects($this->once())
            ->method('setPlural')
            ->with($plural)
            ->willReturnSelf();
        $violation->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($value, $this->constraint);
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
