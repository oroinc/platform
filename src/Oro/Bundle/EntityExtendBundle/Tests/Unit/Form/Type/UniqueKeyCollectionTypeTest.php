<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyCollectionType;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\TranslationBundle\Translation\IdentityTranslator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class UniqueKeyCollectionTypeTest extends FormIntegrationTestCase
{
    private ConfigProvider&MockObject $provider;
    private TranslatorInterface&MockObject $translator;
    private UniqueKeyCollectionType $type;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $validator = new RecursiveValidator(
            new ExecutionContextFactory(new IdentityTranslator()),
            new LazyLoadingMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory()
        );

        $this->type = new UniqueKeyCollectionType($this->provider, $this->translator);

        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->addExtension(new PreloadedExtension([$this->type], []))
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();
    }

    public function testType(): void
    {
        $this->provider->expects(self::once())
            ->method('getIds')
            ->willReturn([
                new FieldConfigId('entity', User::class, 'firstName', 'string'),
                new FieldConfigId('entity', User::class, 'lastName', 'string'),
                new FieldConfigId('entity', User::class, 'email', 'string'),
            ]);

        $firstNameConfig = $this->createMock(ConfigInterface::class);
        $lastNameConfig = $this->createMock(ConfigInterface::class);
        $emailConfig = $this->createMock(ConfigInterface::class);
        $this->provider->expects(self::exactly(3))
            ->method('getConfig')
            ->willReturnOnConsecutiveCalls($firstNameConfig, $lastNameConfig, $emailConfig);
        $firstNameConfig->expects(self::once())
            ->method('get')
            ->with('label')
            ->willReturn('label.first_name');
        $lastNameConfig->expects(self::once())
            ->method('get')
            ->with('label')
            ->willReturn('label.last_name');
        $emailConfig->expects(self::once())
            ->method('get')
            ->with('label')
            ->willReturn(null);
        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        $formData = [
            'keys' => [
                'tag0' => [
                    'name' => 'test key 1',
                    'key'  => []
                ],
                'tag1' => [
                    'name' => 'test key 2',
                    'key'  => []
                ]
            ]
        ];

        $form = $this->factory->create(UniqueKeyCollectionType::class, null, ['className' => 'Test\Entity']);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertEquals($formData, $form->getData());
        self::assertEquals(
            [
                'key_choices' => [
                    'label.first_name (translated)' => 'firstName',
                    'label.last_name (translated)' => 'lastName',
                    'email' => 'email'
                ],
                'block_name' => 'entry'
            ],
            $form->get('keys')->getConfig()->getOption('entry_options')
        );
    }

    public function testNames(): void
    {
        self::assertEquals('oro_entity_extend_unique_key_collection_type', $this->type->getName());
    }
}
