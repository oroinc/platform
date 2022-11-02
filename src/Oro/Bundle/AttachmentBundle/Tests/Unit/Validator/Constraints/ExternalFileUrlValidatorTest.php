<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileUrl;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileUrlValidator;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ExternalFileUrlValidatorTest extends ConstraintValidatorTestCase
{
    use LoggerAwareTraitTestTrait;

    protected function createValidator(): ConstraintValidator
    {
        $validator = new ExternalFileUrlValidator();
        $this->setUpLoggerMock($validator);

        return $validator;
    }

    public function testNoViolationWhenValueIsNull(): void
    {
        $this->validator->validate(null, new ExternalFileUrl());
        $this->assertNoViolation();
    }

    public function testViolationWhenEmptyRegExp(): void
    {
        $externalFile = new ExternalFile('');
        $constraint = new ExternalFileUrl();

        $this->validator->validate($externalFile, $constraint);

        $this->buildViolation($constraint->doesNoMatchRegExpMessage)
            ->setCode(ExternalFileUrl::EMPTY_REGEXP_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenEmptyRegExpAndHasEmptyRegExpMessage(): void
    {
        $externalFile = new ExternalFile('');
        $constraint = new ExternalFileUrl(['emptyRegExpMessage' => 'sample.message']);

        $this->validator->validate($externalFile, $constraint);

        $this->buildViolation($constraint->emptyRegExpMessage)
            ->setCode(ExternalFileUrl::EMPTY_REGEXP_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenInvalidRegExp(): void
    {
        $externalFile = new ExternalFile('');
        $constraint = new ExternalFileUrl(['allowedUrlsRegExp' => '/invalid/regexp/']);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Allowed URLs regular expression ({regexp}) is invalid: {reason}',
                [
                    'regexp' => $constraint->allowedUrlsRegExp,
                    'reason' => 'preg_match(): Unknown modifier \'r\'',
                    'code' => ExternalFileUrl::INVALID_REGEXP_ERROR,
                ]
            );

        $this->validator->validate($externalFile, $constraint);

        $this->buildViolation($constraint->invalidRegExpMessage)
            ->setParameter('{{ code }}', '"' . ExternalFileUrl::INVALID_REGEXP_ERROR . '"')
            ->setCode(ExternalFileUrl::INVALID_REGEXP_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenObjectAndInvalidUrl(): void
    {
        $externalFile = new ExternalFile('http://example.org/image.png');
        $constraint = new ExternalFileUrl(['allowedUrlsRegExp' => '/^http:\/\/valid\.domain/']);

        $this->validator->validate($externalFile, $constraint);

        $this->buildViolation($constraint->doesNoMatchRegExpMessage)
            ->setParameter('{{ regexp }}', '"' . $constraint->allowedUrlsRegExp . '"')
            ->setParameter('{{ value }}', '"' . $externalFile->getUrl() . '"')
            ->setCode(ExternalFileUrl::INVALID_URL_REGEXP_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenScalarAndInvalidUrl(): void
    {
        $constraint = new ExternalFileUrl(['allowedUrlsRegExp' => '/^http:\/\/valid\.domain/']);

        $this->validator->validate('http://example.org/image.png', $constraint);

        $this->buildViolation($constraint->doesNoMatchRegExpMessage)
            ->setParameter('{{ regexp }}', '"' . $constraint->allowedUrlsRegExp . '"')
            ->setParameter('{{ value }}', '"http://example.org/image.png"')
            ->setCode(ExternalFileUrl::INVALID_URL_REGEXP_ERROR)
            ->assertRaised();
    }

    public function testNoViolationWhenObjectAndValidUrl(): void
    {
        $externalFile = new ExternalFile('http://example.org/image.png');
        $constraint = new ExternalFileUrl(['allowedUrlsRegExp' => '/^http:\/\/example\.org/']);

        $this->validator->validate($externalFile, $constraint);

        $this->assertNoViolation();
    }

    public function testNoViolationWhenScalarAndValidUrl(): void
    {
        $constraint = new ExternalFileUrl(['allowedUrlsRegExp' => '/^http:\/\/example\.org/']);

        $this->validator->validate('http://example.org/image.png', $constraint);

        $this->assertNoViolation();
    }
}
