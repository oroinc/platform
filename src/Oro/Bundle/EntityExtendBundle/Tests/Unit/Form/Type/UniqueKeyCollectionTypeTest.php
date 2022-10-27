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
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class UniqueKeyCollectionTypeTest extends FormIntegrationTestCase
{
    private const ENTITY = 'Namespace\Entity';

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var UniqueKeyCollectionType */
    private $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = $this->createMock(ConfigProvider::class);

        $validator = new RecursiveValidator(
            new ExecutionContextFactory(new IdentityTranslator()),
            new LazyLoadingMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory()
        );

        $this->type = new UniqueKeyCollectionType($this->provider);

        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->addExtension(new PreloadedExtension([$this->type], []))
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();
    }

    public function testType()
    {
        $this->provider->expects($this->once())
            ->method('getIds')
            ->willReturn(
                [
                    new FieldConfigId('entity', User::class, 'firstName', 'string'),
                    new FieldConfigId('entity', User::class, 'lastName', 'string'),
                    new FieldConfigId('entity', User::class, 'email', 'string'),
                ]
            );

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->exactly(3))
            ->method('get')
            ->with('label')
            ->willReturn('label');

        $this->provider->expects($this->exactly(3))
            ->method('getConfig')
            ->willReturn($config);

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

        $form = $this->factory->create(UniqueKeyCollectionType::class, null, ['className' => self::ENTITY]);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertEquals($formData, $form->getData());
    }

    public function testNames()
    {
        $this->assertEquals('oro_entity_extend_unique_key_collection_type', $this->type->getName());
    }
}
