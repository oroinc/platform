<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Form\DataTransformer\ExternalFileTransformer;
use Oro\Bundle\AttachmentBundle\Form\Type\ExternalFileType;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExternalFileTypeTest extends FormIntegrationTestCase
{
    private const URL = 'http://example.org/image.png';
    private const URL_NOT_ACCESSIBLE = 'http://example.org/image2.png';
    private const URL_INVALID = 'invalid_url';

    private ExternalFileFactory|\PHPUnit\Framework\MockObject\MockObject $externalFileFactory;

    private ConfigFileValidator|\PHPUnit\Framework\MockObject\MockObject $configFileValidator;

    private ExternalFileType $type;

    protected function getExtensions(): array
    {
        $this->configFileValidator = $this->createMock(ConfigFileValidator::class);
        $this->externalFileFactory = $this->createMock(ExternalFileFactory::class);

        $externalFileTransformer = new ExternalFileTransformer($this->configFileValidator, $this->externalFileFactory);

        $this->type = new ExternalFileType($externalFileTransformer);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        ExternalFileType::class => $this->type,
                    ],
                    []
                ),
                new ValidatorExtension(Validation::createValidator()),
            ]
        );
    }

    public function testGetParent(): void
    {
        self::assertEquals(UrlType::class, $this->type->getParent());
    }

    public function testConfigureOptions(): void
    {
        $optionsResolver = new OptionsResolver();
        $this->type->configureOptions($optionsResolver);

        self::assertSame([
            'data_class' => ExternalFile::class,
            'empty_data' => null,
        ], $optionsResolver->resolve([]));
    }

    public function testBuildView(): void
    {
        $form = $this->factory->create(ExternalFileType::class);
        $formView = $form->createView();

        self::assertArrayHasKey('attr', $formView->vars);
        self::assertArrayHasKey('data-is-external-file', $formView->vars['attr']);
        self::assertEquals('1', $formView->vars['attr']['data-is-external-file']);
    }

    public function testSubmitWhenNoInitialDataAndEmptySubmittedData(): void
    {
        $form = $this->factory->create(ExternalFileType::class);

        self::assertNull($form->getData());

        $form->submit('');

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testSubmitWhenInitialDataButEmptySubmittedData(): void
    {
        $externalFile = new ExternalFile(self::URL);
        $form = $this->factory->create(ExternalFileType::class, $externalFile);

        self::assertSame($externalFile, $form->getData());

        $form->submit('');

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testSubmitWhenNoInitialDataButHasSubmittedData(): void
    {
        $form = $this->factory->create(ExternalFileType::class);

        self::assertNull($form->getData());

        $externalFile = new ExternalFile(self::URL);
        $this->externalFileFactory
            ->expects(self::once())
            ->method('createFromUrl')
            ->willReturn($externalFile);

        $form->submit(self::URL);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($externalFile, $form->getData());
    }

    public function testSubmitWhenInitialDataAndSubmittedData(): void
    {
        $externalFile = new ExternalFile(self::URL);
        $form = $this->factory->create(ExternalFileType::class, $externalFile);

        self::assertSame($externalFile, $form->getData());

        $newExternalFile = new ExternalFile(self::URL_NOT_ACCESSIBLE);
        $this->externalFileFactory
            ->expects(self::once())
            ->method('createFromUrl')
            ->willReturn($newExternalFile);

        $form->submit(self::URL_NOT_ACCESSIBLE);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertSame($newExternalFile, $form->getData());
    }

    public function testSubmitWhenInitialDataAndSubmittedDataIsSame(): void
    {
        $externalFile = new ExternalFile(self::URL, 'original-image.png', 4242, 'image/png');
        $form = $this->factory->create(ExternalFileType::class, $externalFile);

        self::assertSame($externalFile, $form->getData());

        $this->externalFileFactory
            ->expects(self::never())
            ->method('createFromUrl');

        $form->submit(self::URL);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertSame($externalFile, $form->getData());
    }

    public function testSubmitWhenInitialDataAndSubmittedDataIsInvalid(): void
    {
        $externalFile = new ExternalFile(self::URL);

        $form = $this->factory->create(ExternalFileType::class, $externalFile);

        self::assertSame($externalFile, $form->getData());

        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->willReturn(new ConstraintViolationList());

        $this->externalFileFactory
            ->expects(self::once())
            ->method('createFromUrl')
            ->willThrowException(new ExternalFileNotAccessibleException(self::URL_NOT_ACCESSIBLE, 'Not Found'));

        $form->submit(self::URL_NOT_ACCESSIBLE);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->getErrors());
        $formError = $form->getErrors()[0];
        self::assertEquals('oro.attachment.external_file.invalid_url', $formError->getMessage());
        self::assertEquals('oro.attachment.external_file.invalid_url', $formError->getMessageTemplate());
        self::assertEquals(
            ['{{ reason }}' => 'Not Found', '{{ value }}' => self::URL_NOT_ACCESSIBLE],
            $formError->getMessageParameters()
        );
        self::assertFalse($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testSubmitWhenInitialDataAndSubmittedDataDoesNotMatchRegExp(): void
    {
        $externalFile = new ExternalFile(self::URL);

        $form = $this->factory->create(ExternalFileType::class, $externalFile);

        self::assertSame($externalFile, $form->getData());

        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->willReturn(
                new ConstraintViolationList(
                    [
                        new ConstraintViolation(
                            'sample.message',
                            'sample.message',
                            ['{{ sample_key }}' => 'sample_value'],
                            null,
                            null,
                            self::URL_INVALID
                        ),
                    ]
                )
            );

        $this->externalFileFactory
            ->expects(self::never())
            ->method('createFromUrl');

        $form->submit(self::URL_INVALID);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->getErrors());
        $formError = $form->getErrors()[0];
        self::assertEquals('sample.message', $formError->getMessage());
        self::assertEquals('sample.message', $formError->getMessageTemplate());
        self::assertEquals(
            ['{{ value }}' => self::URL_INVALID, '{{ sample_key }}' => 'sample_value'],
            $formError->getMessageParameters()
        );
        self::assertFalse($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testSubmitWhenNoInitialDataAndSubmittedDataIsInvalid(): void
    {
        $form = $this->factory->create(ExternalFileType::class);

        self::assertNull($form->getData());

        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->willReturn(new ConstraintViolationList());

        $this->externalFileFactory
            ->expects(self::once())
            ->method('createFromUrl')
            ->willThrowException(new ExternalFileNotAccessibleException(self::URL_NOT_ACCESSIBLE, 'Not Found'));

        $form->submit(self::URL_NOT_ACCESSIBLE);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->getErrors());
        $formError = $form->getErrors()[0];
        self::assertEquals('oro.attachment.external_file.invalid_url', $formError->getMessage());
        self::assertEquals('oro.attachment.external_file.invalid_url', $formError->getMessageTemplate());
        self::assertEquals(
            ['{{ reason }}' => 'Not Found', '{{ value }}' => self::URL_NOT_ACCESSIBLE],
            $formError->getMessageParameters()
        );
        self::assertFalse($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testSubmitWhenNoInitialDataAndSubmittedDataDoesNotMatchRegExp(): void
    {
        $form = $this->factory->create(ExternalFileType::class);

        self::assertNull($form->getData());

        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->willReturn(
                new ConstraintViolationList(
                    [
                        new ConstraintViolation(
                            'sample.message',
                            'sample.message',
                            ['{{ sample_key }}' => 'sample_value'],
                            null,
                            null,
                            self::URL_INVALID
                        ),
                    ]
                )
            );

        $this->externalFileFactory
            ->expects(self::never())
            ->method('createFromUrl');

        $form->submit(self::URL_INVALID);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->getErrors());
        $formError = $form->getErrors()[0];
        self::assertEquals('sample.message', $formError->getMessage());
        self::assertEquals('sample.message', $formError->getMessageTemplate());
        self::assertEquals(
            ['{{ value }}' => self::URL_INVALID, '{{ sample_key }}' => 'sample_value'],
            $formError->getMessageParameters()
        );
        self::assertFalse($form->isSynchronized());
        self::assertNull($form->getData());
    }
}
