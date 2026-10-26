<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileMimeType;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileMimeTypeValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ExternalFileMimeTypeValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): ConstraintValidator
    {
        return new ExternalFileMimeTypeValidator();
    }

    public function testNoViolationWhenValueIsNull(): void
    {
        $this->validator->validate(null, new ExternalFileMimeType());
        $this->assertNoViolation();
    }

    public function testViolationWhenObjectAndEmptyMimeType(): void
    {
        $externalFile = new ExternalFile('');
        $constraint = new ExternalFileMimeType();

        $this->validator->validate($externalFile, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', '""')
            ->setParameter('{{ types }}', '')
            ->setCode(ExternalFileMimeType::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenScalarAndEmptyMimeType(): void
    {
        $constraint = new ExternalFileMimeType();

        $this->validator->validate('', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', '""')
            ->setParameter('{{ types }}', '')
            ->setCode(ExternalFileMimeType::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenObjectAndMimeTypeIsNotAllowed(): void
    {
        $externalFile = new ExternalFile('http://example.org/image.png', 'image.png', 0, 'image/png');
        $constraint = new ExternalFileMimeType(['mimeTypes' => ['image/jpeg', 'image/webp']]);

        $this->validator->validate($externalFile, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', '"image/png"')
            ->setParameter('{{ types }}', '"image/jpeg", "image/webp"')
            ->setCode(ExternalFileMimeType::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenScalarAndMimeTypeIsNotAllowed(): void
    {
        $constraint = new ExternalFileMimeType(['mimeTypes' => ['image/jpeg', 'image/webp']]);

        $this->validator->validate('image/png', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', '"image/png"')
            ->setParameter('{{ types }}', '"image/jpeg", "image/webp"')
            ->setCode(ExternalFileMimeType::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testNoViolationWhenObjectAndMimeTypeIsAllowed(): void
    {
        $externalFile = new ExternalFile('http://example.org/image.png', 'image.png', 0, 'image/png');
        $constraint = new ExternalFileMimeType(['mimeTypes' => ['image/png', 'image/webp']]);

        $this->validator->validate($externalFile, $constraint);

        $this->assertNoViolation();
    }

    public function testNoViolationWhenScalarAndMimeTypeIsAllowed(): void
    {
        $constraint = new ExternalFileMimeType(['mimeTypes' => ['image/png', 'image/webp']]);

        $this->validator->validate('image/png', $constraint);

        $this->assertNoViolation();
    }

    public function testNoViolationWhenMimeTypeMatchesGroupWildcard(): void
    {
        $externalFile = new ExternalFile('http://example.org/image.png', 'image.png', 0, 'image/png');
        $constraint = new ExternalFileMimeType(['mimeTypes' => ['application/pdf', 'image/*']]);

        $this->validator->validate($externalFile, $constraint);

        $this->assertNoViolation();
    }

    public function testViolationWhenMimeTypeDoesNotMatchGroupWildcard(): void
    {
        $externalFile = new ExternalFile('http://example.org/file.txt', 'file.txt', 0, 'text/plain');
        $constraint = new ExternalFileMimeType(['mimeTypes' => ['image/*']]);

        $this->validator->validate($externalFile, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', '"text/plain"')
            ->setParameter('{{ types }}', '"image/*"')
            ->setCode(ExternalFileMimeType::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenMimeTypeOfAnotherGroupStartsWithTheSameLetters(): void
    {
        $constraint = new ExternalFileMimeType(['mimeTypes' => ['image/*']]);

        $this->validator->validate('imagex/png', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', '"imagex/png"')
            ->setParameter('{{ types }}', '"image/*"')
            ->setCode(ExternalFileMimeType::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenWildcardHasNoGroupAndMimeTypeStartsWithSlash(): void
    {
        $constraint = new ExternalFileMimeType(['mimeTypes' => ['/*']]);

        $this->validator->validate('/png', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', '"/png"')
            ->setParameter('{{ types }}', '"/*"')
            ->setCode(ExternalFileMimeType::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }
}
