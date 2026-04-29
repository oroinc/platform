<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\IntegrationBundle\Entity\WebhookConsumerSettings;
use Oro\Bundle\IntegrationBundle\Form\Type\WebhookFieldType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class WebhookFieldTypeTest extends FormIntegrationTestCase
{
    private ManagerRegistry&MockObject $registry;
    private ObjectRepository&MockObject $repository;
    private WebhookFieldType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ObjectRepository::class);

        $this->registry->expects(self::any())
            ->method('getRepository')
            ->with(WebhookConsumerSettings::class)
            ->willReturn($this->repository);

        $this->formType = new WebhookFieldType($this->registry);
        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(WebhookFieldType::class, null, [
            'webhook_processor' => 'test_processor'
        ]);

        self::assertTrue($form->getConfig()->hasOption('webhook_processor'));
        self::assertEquals('test_processor', $form->getConfig()->getOption('webhook_processor'));
    }

    public function testConfigureOptionsWebhookProcessorIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "webhook_processor" is missing.');

        $this->factory->create(WebhookFieldType::class);
    }

    public function testConfigureOptionsWebhookProcessorMustBeString(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->factory->create(WebhookFieldType::class, null, [
            'webhook_processor' => 123
        ]);
    }

    public function testGetParent(): void
    {
        self::assertEquals(HiddenType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals(WebhookFieldType::NAME, $this->formType->getBlockPrefix());
    }

    public function testSubmitWithExistingWebhook(): void
    {
        $existingWebhook = new WebhookConsumerSettings();
        $existingWebhook->setId('existing-uuid');
        $existingWebhook->setProcessor('existing_processor');

        $this->repository->expects(self::once())
            ->method('find')
            ->with('existing-uuid')
            ->willReturn($existingWebhook);

        $form = $this->factory->create(WebhookFieldType::class, null, [
            'webhook_processor' => 'test_processor'
        ]);

        $form->submit('existing-uuid');

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        $result = $form->getData();
        self::assertInstanceOf(WebhookConsumerSettings::class, $result);
        self::assertEquals('existing-uuid', $result->getId());
        self::assertEquals('existing_processor', $result->getProcessor());
    }

    public function testSubmitWithNewWebhook(): void
    {
        $this->repository->expects(self::once())
            ->method('find')
            ->with('new-uuid')
            ->willReturn(null);

        $form = $this->factory->create(WebhookFieldType::class, null, [
            'webhook_processor' => 'test_processor'
        ]);

        $form->submit('new-uuid');

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        $result = $form->getData();
        self::assertInstanceOf(WebhookConsumerSettings::class, $result);
        self::assertEquals('new-uuid', $result->getId());
        self::assertEquals('test_processor', $result->getProcessor());
    }

    public function testSubmitWithNull(): void
    {
        $form = $this->factory->create(WebhookFieldType::class, null, [
            'webhook_processor' => 'test_processor'
        ]);

        $form->submit(null);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testSubmitWithEmptyString(): void
    {
        $form = $this->factory->create(WebhookFieldType::class, null, [
            'webhook_processor' => 'test_processor'
        ]);

        $form->submit('');

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }
}
