<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class FieldTypeReverseRelationTest extends \PHPUnit_Framework_TestCase
{
    /** @var  FieldType $type */
    protected $type;

    /** @var  \ReflectionClass */
    protected $typeReflection;

    /** @var  FormFactory */
    protected $factory;

    /** @var  ConfigManager */
    protected $configManagerMock;

    /** @var  Translator */
    protected $translatorMock;

    protected $defaultFieldTypesKeys = [
        'string',
        'integer',
        'smallint',
        'bigint',
        'boolean',
        'decimal',
        'date',
        'text',
        'float',
        'money',
        'percent',
        'oneToMany',
        'manyToOne',
        'manyToMany',
        'optionSet'
    ];

    protected $formOptions = array(
        'class_name' => 'Oro\Bundle\UserBundle\Entity\User'
    );

    protected function setUp()
    {
        parent::setUp();

        $validator = new Validator(
            new ClassMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory(),
            new DefaultTranslator()
        );

        $this->factory           = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();
        $this->configManagerMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translatorMock    = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translatorMock
            ->expects($this->any())
            ->method('trans')
            ->will(
                $this->returnCallback(
                    function ($param) {
                        return $param;
                    }
                )
            );

        $this->type           = new FieldType(
            $this->configManagerMock,
            $this->translatorMock,
            new ExtendDbIdentifierNameGenerator()
        );
        $this->typeReflection = new \ReflectionClass($this->type);
    }

    public function testTypeWithReverseRelationManyToOne()
    {
        $this->prepareTestTypeWithRelations();

        $form = $this->factory->create($this->type, null, $this->formOptions);

        $this->assertEquals(
            array_merge(
                $this->defaultFieldTypesKeys,
                ['manyToOne|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel_m_t_o||']
            ),
            array_keys($form->offsetGet('type')->getConfig()->getOption('choices'))
        );

        $form->submit(['fieldName' => 'rev_rel_m_t_o', 'type' => 'oneToMany']);
        $this->assertTrue($form->isSynchronized());
    }

    protected function prepareReverseRelationsConfig()
    {
        $targetFieldId  = new FieldConfigId(
            'extend',
            'Extend\Entity\testEntity1',
            'rel_m_t_o',
            'manyToOne'
        );
        $relationConfig = [
            'manyToOne|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel_m_t_o' => [
                'assign'          => false,
                'field_id'        => false,
                'owner'           => false,
                'target_entity'   => 'Extend\Entity\testEntity1',
                'target_field_id' => $targetFieldId
            ],
            'oneToMany|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel_o_t_m' => [
                'assign'          => true,
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Extend\Entity\testEntity1',
                    'rel_o_t_m',
                    'oneToMany'
                ),
                'owner'           => true,
                'target_entity'   => 'Extend\Entity\testEntity1',
                'target_field_id' => false
            ]
        ];

        $targetEntityConfig   = new Config(new EntityConfigId('extend', 'Extend\Entity\testEntity1'));
        $targetEntityRelation = [
            'manyToOne|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel_m_t_o' => [
                'assign'          => true,
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Extend\Entity\testEntity1',
                    'rel_m_t_o',
                    'manyToOne'
                ),
                'owner'           => true,
                'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                'target_field_id' => false
            ]
        ];
        $targetEntityConfig->set('relation', $targetEntityRelation);

        return [
            'targetFieldId'      => $targetFieldId,
            'relationConfig'     => $relationConfig,
            'targetEntityConfig' => $targetEntityConfig
        ];
    }

    protected function prepareTestTypeWithRelations()
    {
        $config = $this->prepareReverseRelationsConfig();

        $entityConfigMockUser = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->setConstructorArgs([new EntityConfigId('extend', 'Oro\Bundle\UserBundle\Entity\User')])
            ->setMockClassName('entityConfigMockUser')
            ->setMethods(null)
            ->getMock();
        $entityConfigMockUser->set('relation', $config['relationConfig']);

        $entityConfigMockTarget = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->setConstructorArgs([$config['targetEntityConfig']->getId()])
            ->setMockClassName('entityConfigMockTarget')
            ->setMethods(null)
            ->getMock();
        $entityConfigMockTarget->set('relation', $config['targetEntityConfig']->get('relation'));

        $configProviderMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->setMockClassName('configProviderMock')
            ->getMock();

        $configProviderMock
            ->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnCallback(
                    function ($param) use ($entityConfigMockUser, $entityConfigMockTarget) {
                        switch ($param) {
                            case 'Oro\Bundle\UserBundle\Entity\User':
                                return $entityConfigMockUser;
                            case 'Extend\Entity\testEntity1':
                                return $entityConfigMockTarget;
                        }
                    }
                )
            );
        $configProviderMock
            ->expects($this->any())
            ->method('getConfigById')
            ->with($config['targetFieldId'])
            ->will($this->returnValue($entityConfigMockTarget));

        $this->configManagerMock
            ->expects($this->exactly(2))
            ->method('getProvider')
            ->will($this->returnValue($configProviderMock));
    }
}
