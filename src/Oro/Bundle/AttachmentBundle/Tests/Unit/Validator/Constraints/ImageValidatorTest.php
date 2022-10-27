<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\Image;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ImageValidator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ImageValidatorTest extends ConstraintValidatorTestCase
{
    /** @var File|\PHPUnit\Framework\MockObject\MockObject */
    private $image;

    protected function setUp(): void
    {
        $this->image = $this->createMock(File::class);

        // File must be exist and readable
        $this->image->expects($this->any())
            ->method('getPathname')
            ->willReturn(__FILE__);

        parent::setUp();
    }

    protected function createValidator(): ImageValidator
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_attachment.upload_image_mime_types')
            ->willReturn(MimeTypesConverter::convertToString(['image/png', 'image/jpg', 'image/gif']));

        return new ImageValidator($configManager);
    }

    public function testValidImage(): void
    {
        $this->image->expects($this->any())
            ->method('getMimeType')
            ->willReturn('image/jpg');

        $constraint = new Image();
        $this->validator->validate($this->image, $constraint);
        $this->assertNoViolation();
    }

    public function testInvalidImage(): void
    {
        $this->image->expects($this->any())
            ->method('getMimeType')
            ->willReturn('image/svg');

        $constraint = new Image();
        $this->validator->validate($this->image, $constraint);

        $this->buildViolation($constraint->mimeTypesMessage)
            ->setCode(Image::INVALID_MIME_TYPE_ERROR)
            ->setParameter('{{ file }}', '"' . __FILE__ . '"')
            ->setParameter('{{ types }}', '"image/png", "image/jpg", "image/gif"')
            ->setParameter('{{ type }}', '"image/svg"')
            ->setParameter('{{ name }}', '"ImageValidatorTest.php"')
            ->assertRaised();
    }
}
