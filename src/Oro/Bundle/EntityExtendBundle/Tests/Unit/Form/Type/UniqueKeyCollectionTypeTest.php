<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyCollectionType;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class UniqueKeyCollectionTypeTest extends FormIntegrationTestCase
{
    const ENTITY = 'Namespace\Entity';

    /**
     * @var UniqueKeyCollectionType
     */
    protected $type;

    /**
     * @var FieldConfigId[]
     */
    protected $fields;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->provider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

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
        $this->provider
            ->expects($this->once())
            ->method('getIds')
            ->will(
                $this->returnValue(
                    [
                        new FieldConfigId('entity', 'Oro\Bundle\UserBundle\Entity\User', 'firstName', 'string'),
                        new FieldConfigId('entity', 'Oro\Bundle\UserBundle\Entity\User', 'lastName', 'string'),
                        new FieldConfigId('entity', 'Oro\Bundle\UserBundle\Entity\User', 'email', 'string'),
                    ]
                )
            );

        $config = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config
            ->expects($this->exactly(3))
            ->method('get')
            ->with($this->equalTo('label'))
            ->will($this->returnValue('label'));

        $this->provider
            ->expects($this->exactly(3))
            ->method('getConfig')
            ->will($this->returnValue($config));

        $formData = array(
            'keys' => array(
                'tag0' => array(
                    'name' => 'test key 1',
                    'key'  => array()
                ),
                'tag1' => array(
                    'name' => 'test key 2',
                    'key'  => array()
                )
            )
        );

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
