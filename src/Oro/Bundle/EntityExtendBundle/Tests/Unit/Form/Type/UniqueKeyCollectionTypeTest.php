<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyCollectionType;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;

class UniqueKeyCollectionTypeTest extends TypeTestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->provider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $validator = new Validator(
            new ClassMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory(),
            new DefaultTranslator()
        );

        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();

        $this->type = new UniqueKeyCollectionType($this->provider);
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

        $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
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

        $form = $this->factory->create($this->type, null, ['className' => self::ENTITY]);
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
