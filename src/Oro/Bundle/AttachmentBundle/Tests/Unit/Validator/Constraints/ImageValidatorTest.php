<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\Image;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ImageValidator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @property Image $constraint
 */
class ImageValidatorTest extends ConstraintValidatorTestCase
{
    /** @var File|MockObject */
    private $image;

    /** @var ConfigManager|MockObject */
    private $configManager;

    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        $this->image = $this->createMock(File::class);

        // File must be exist and readable
        $this->image->expects($this->any())
            ->method('getPathname')
            ->willReturn(__FILE__);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_attachment.upload_image_mime_types')
            ->willReturn(MimeTypesConverter::convertToString(['image/png', 'image/jpg', 'image/gif']));

        return new ImageValidator($this->configManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new Image();

        return parent::createContext();
    }

    public function testConfiguration(): void
    {
        $this->assertEquals('oro_attachment_image_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testValidImage(): void
    {
        $this->image->expects($this->any())
            ->method('getMimeType')
            ->willReturn('image/jpg');

        $this->validator->validate($this->image, $this->constraint);
        $this->assertNoViolation();
    }

    public function testInvalidImage(): void
    {
        $this->image->expects($this->any())
            ->method('getMimeType')
            ->willReturn('image/svg');

        $this->validator->validate($this->image, $this->constraint);
        $this->buildViolation($this->constraint->mimeTypesMessage)
            ->setCode(Image::INVALID_MIME_TYPE_ERROR)
            ->setParameter('{{ file }}', '"' . __FILE__ . '"')
            ->setParameter('{{ types }}', '"image/png", "image/jpg", "image/gif"')
            ->setParameter('{{ type }}', '"image/svg"')
            ->setParameter('{{ name }}', '"ImageValidatorTest.php"')
            ->assertRaised();
    }
}
