<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Common;

use Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Form\Type\BooleanType;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Tests\Functional\TestMetadataAccessor;
use Oro\Bundle\ApiBundle\Tests\Functional\TestObject;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\DataAccessor\PropertyPathAccessor;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FormTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    protected function tearDown(): void
    {
        $this->switchToDefaultFormExtension();
        $this->setMetadataAccessor(null);
        parent::tearDown();
    }

    public function testDefaultForm()
    {
        $form = $this->getForm(['csrf_protection' => false]);
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => 'test']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
        self::assertTrue($form->isValid(), 'isValid');

        self::assertSame(123, $object->getId());
        self::assertSame('test', $object->getTitle());
    }

    public function testApiForm()
    {
        $this->switchToApiFormExtension();

        $form = $this->getForm();
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => 'test']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertTrue($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');

        self::assertSame(123, $object->getId());
        self::assertSame('test', $object->getTitle());
    }

    public function testDefaultFormWithFormTypeThatDoesNotExistInApi()
    {
        $form = $this->getRootForm(['csrf_protection' => false]);
        $form->add('title', HiddenType::class);
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['title' => 'test']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertTrue($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');

        self::assertSame('test', $object->getTitle());
    }

    public function testApiFormWithFormTypeThatDoesNotExistInApi()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The form type "Symfony\Component\Form\Extension\Core\Type\HiddenType" is not configured to be used in API.'
        );

        $this->switchToApiFormExtension();

        $form = $this->getRootForm();
        $form->add('title', HiddenType::class);
    }

    public function testApiFormWithApiSpecificFormType()
    {
        $this->switchToApiFormExtension();

        // test API boolean form type, TRUE value
        $form = $this->getRootForm();
        $form->add('title', TextType::class);
        $form->add('enabled', BooleanType::class);
        $object = new TestObject();
        $form->setData($object);
        $form->submit(['title' => 'test', 'enabled' => 'yes']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted, TRUE');
        self::assertTrue($form->isValid(), 'isValid, TRUE');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
        self::assertTrue($object->isEnabled(), 'value, TRUE');

        // test API boolean form type, FALSE value
        $form = $this->getRootForm();
        $form->add('title', TextType::class);
        $form->add('enabled', BooleanType::class);
        $object = new TestObject();
        $form->setData($object);
        $form->submit(['title' => 'test', 'enabled' => 'no']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted, FALSE');
        self::assertTrue($form->isValid(), 'isValid, FALSE');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
        self::assertFalse($object->isEnabled(), 'value, FALSE');

        // test API boolean form type, NULL value
        $form = $this->getRootForm();
        $form->add('title', TextType::class);
        $form->add('enabled', BooleanType::class);
        $object = new TestObject();
        $form->setData($object);
        $form->submit(['title' => 'test', 'enabled' => null]);
        self::assertTrue($form->isSubmitted(), 'isSubmitted, NULL');
        self::assertTrue($form->isValid(), 'isValid, NULL');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
        self::assertNull($object->isEnabled(), 'value, NULL');
    }

    public function testDefaultFormValidation()
    {
        $form = $this->getForm(['csrf_protection' => false]);
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => '']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertFalse($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
    }

    public function testSetNullToNotNullableProperty()
    {
        $form = $this->getForm(['csrf_protection' => false]);
        $object = new TestObject();
        $object->setTitle('test');
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => null]);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertFalse($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
    }

    public function testApiFormValidation()
    {
        $this->switchToApiFormExtension();

        $form = $this->getForm();
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => '']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertFalse($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
    }

    public function testValidationConstraintFromAllGroups()
    {
        $this->switchToApiFormExtension();

        $form = $this->getForm();
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => 'test', 'description' => '12']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertTrue($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
    }

    public function testValidationConstraintFromApiGroup()
    {
        $this->switchToApiFormExtension();

        $form = $this->getForm(['validation_groups' => ['Default', 'api']]);
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => 'test', 'description' => '1234']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertFalse($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
    }

    public function testValidationConstraintFromUiGroup()
    {
        $this->switchToApiFormExtension();

        $form = $this->getForm(['validation_groups' => ['Default', 'ui']]);
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => 'test', 'description' => '1234']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertTrue($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');
    }

    public function testApiFormShouldNotHaveCsrfExtension()
    {
        $this->expectException(UndefinedOptionsException::class);

        $this->switchToApiFormExtension();

        $this->getForm(['csrf_protection' => false]);
    }

    public function testApiFormWithGuessedTypes()
    {
        $this->switchToApiFormExtension();

        $metadata = $this->getEntityMetadata();
        $this->setMetadataAccessor(new TestMetadataAccessor([$metadata]));

        $form = $this->getFormWithGuessedTypes();
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['id' => 123, 'title' => 'test']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertTrue($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');

        self::assertSame(123, $object->getId());
        self::assertSame('test', $object->getTitle());
    }

    public function testApiFormWithGuessedTypesAndPropertyPath()
    {
        $this->switchToApiFormExtension();

        $metadata = $this->getEntityMetadata();
        $metadata->setIdentifierFieldNames(['code']);
        $metadata->renameField('id', 'code');
        $this->setMetadataAccessor(new TestMetadataAccessor([$metadata]));

        $form = $this->getRootForm();
        $form->add('code', null, ['property_path' => 'id']);
        $form->add('title');
        $object = new TestObject();
        $form->setData($object);

        $form->submit(['code' => 123, 'title' => 'test']);
        self::assertTrue($form->isSubmitted(), 'isSubmitted');
        self::assertTrue($form->isValid(), 'isValid');
        self::assertTrue($form->isSynchronized(), 'isSynchronized');

        self::assertSame(123, $object->getId());
        self::assertSame('test', $object->getTitle());
    }

    private function switchToDefaultFormExtension(): void
    {
        /** @var FormExtensionSwitcherInterface $formExtensionSwitcher */
        $formExtensionSwitcher = self::getContainer()->get('form.registry');
        $formExtensionSwitcher->switchToDefaultFormExtension();
    }

    private function switchToApiFormExtension(): void
    {
        /** @var FormExtensionSwitcherInterface $formExtensionSwitcher */
        $formExtensionSwitcher = self::getContainer()->get('form.registry');
        $formExtensionSwitcher->switchToApiFormExtension();
    }

    private function setMetadataAccessor(?MetadataAccessorInterface $metadataAccessor): void
    {
        /** @var MetadataTypeGuesser $metadataTypeGuesser */
        $metadataTypeGuesser = self::getContainer()->get('oro_api.form.guesser.metadata');
        $metadataTypeGuesser->setMetadataAccessor($metadataAccessor);
    }

    private function getForm(array $options = []): FormInterface
    {
        $form = $this->getRootForm($options);
        $form->add('id', IntegerType::class);
        $form->add('title', TextType::class);
        $form->add('description', TextType::class);

        return $form;
    }

    private function getFormWithGuessedTypes(array $options = []): FormInterface
    {
        $form = $this->getRootForm($options);
        $form->add('id');
        $form->add('title');

        return $form;
    }

    private function getRootForm(array $options = []): FormInterface
    {
        if (!isset($options['data_class'])) {
            $options['data_class'] = TestObject::class;
        }
        $options['extra_fields_message'] = FormHelper::EXTRA_FIELDS_MESSAGE;

        return self::getContainer()->get('form.factory')
            ->createBuilder(FormType::class, null, $options)
            ->setDataMapper(new DataMapper(new PropertyPathAccessor(
                self::getContainer()->get('oro_api.form_property_accessor')
            )))
            ->getForm();
    }

    private function getEntityMetadata(): EntityMetadata
    {
        $metadata = new EntityMetadata(TestObject::class);
        $metadata->setIdentifierFieldNames(['id']);
        $idField = new FieldMetadata();
        $idField->setName('id');
        $idField->setDataType('integer');
        $idField->setIsNullable(true);
        $metadata->addField($idField);
        $titleField = new FieldMetadata();
        $titleField->setName('title');
        $titleField->setDataType('string');
        $titleField->setIsNullable(true);
        $metadata->addField($titleField);

        return $metadata;
    }
}
