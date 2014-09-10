<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
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

class FieldTypeReverseRelationTest extends TypeTestCase
{
    const FIELDS_GROUP       = 'oro.entity_extend.form.data_type_group.fields';
    const RELATIONS_GROUP    = 'oro.entity_extend.form.data_type_group.relations';

    /** @var  FieldType $type */
    protected $type;

    /** @var  \ReflectionClass */
    protected $typeReflection;

    /** @var  ConfigManager */
    protected $configManagerMock;

    /** @var  Translator */
    protected $translatorMock;

    protected $defaultFieldTypeChoices = [
        self::FIELDS_GROUP       => [
            'bigint'    => 'oro.entity_extend.form.data_type.bigint',
            'boolean'   => 'oro.entity_extend.form.data_type.boolean',
            'date'      => 'oro.entity_extend.form.data_type.date',
            'datetime'  => 'oro.entity_extend.form.data_type.datetime',
            'decimal'   => 'oro.entity_extend.form.data_type.decimal',
            'enum'      => 'oro.entity_extend.form.data_type.enum',
            'file'      => 'oro.entity_extend.form.data_type.file',
            'float'     => 'oro.entity_extend.form.data_type.float',
            'image'     => 'oro.entity_extend.form.data_type.image',
            'integer'   => 'oro.entity_extend.form.data_type.integer',
            'money'     => 'oro.entity_extend.form.data_type.money',
            'multiEnum' => 'oro.entity_extend.form.data_type.multiEnum',
            'optionSet' => 'oro.entity_extend.form.data_type.optionSet',
            'percent'   => 'oro.entity_extend.form.data_type.percent',
            'smallint'  => 'oro.entity_extend.form.data_type.smallint',
            'string'    => 'oro.entity_extend.form.data_type.string',
            'text'      => 'oro.entity_extend.form.data_type.text',
        ],
        self::RELATIONS_GROUP    => [
            'manyToMany' => 'oro.entity_extend.form.data_type.manyToMany',
            'manyToOne'  => 'oro.entity_extend.form.data_type.manyToOne',
            'oneToMany'  => 'oro.entity_extend.form.data_type.oneToMany',
        ],
    ];

    protected $formOptions = array(
        'class_name' => 'Oro\Bundle\UserBundle\Entity\User'
    );

    protected function setUp()
    {
        parent::setUp();

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

    protected function getExtensions()
    {
        $validator = new Validator(
            new ClassMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory(),
            new DefaultTranslator()
        );

        $select2ChoiceType = new Select2Type('choice');

        return array(
            new PreloadedExtension(
                [
                    $select2ChoiceType->getName() => $select2ChoiceType,
                ],
                [
                    'form' => [
                        new DataBlockExtension(),
                        new FormTypeValidatorExtension($validator)
                    ]
                ]
            )
        );
    }

    public function testTypeWithReverseRelationManyToOne()
    {
        $this->prepareTestTypeWithRelations();

        $form = $this->factory->create($this->type, null, $this->formOptions);

        $expectedChoices = $this->defaultFieldTypeChoices;
        $typeName        = 'manyToOne|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel_m_t_o||';

        $expectedChoices[self::RELATIONS_GROUP] = array_merge(
            $expectedChoices[self::RELATIONS_GROUP],
            [$typeName => 'oro.entity_extend.form.data_type.inverse_relation']
        );
        $this->assertSame(
            $expectedChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'rev_rel_m_t_o', 'type' => 'oneToMany']);
        $this->assertTrue($form->isSynchronized());
    }

    protected function prepareReverseRelationsConfig()
    {
        $selfEntityConfig   = new Config(new EntityConfigId('extend', 'Oro\Bundle\UserBundle\Entity\User'));
        $selfTargetFieldId  = new FieldConfigId(
            'extend',
            'Extend\Entity\testEntity1',
            'rel_m_t_o',
            'manyToOne'
        );
        $selfEntityRelation = [
            'manyToOne|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel_m_t_o' => [
                'assign'          => false,
                'field_id'        => false,
                'owner'           => false,
                'target_entity'   => 'Extend\Entity\testEntity1',
                'target_field_id' => $selfTargetFieldId
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
        $selfEntityConfig->set('relation', $selfEntityRelation);

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
            'selfEntityConfig'   => $selfEntityConfig,
            'targetFieldId'      => $selfTargetFieldId,
            'targetEntityConfig' => $targetEntityConfig
        ];
    }

    protected function prepareTestTypeWithRelations()
    {
        $config = $this->prepareReverseRelationsConfig();

        $entityConfigMockUser = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->setConstructorArgs([$config['selfEntityConfig']->getId()])
            ->setMockClassName('entityConfigMockUser')
            ->setMethods(null)
            ->getMock();
        $entityConfigMockUser->set('relation', $config['selfEntityConfig']->get('relation'));

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

        $configProviderMock->expects($this->any())
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
        $configProviderMock->expects($this->any())
            ->method('getConfigById')
            ->with($config['targetFieldId'])
            ->will($this->returnValue($entityConfigMockTarget));

        $this->configManagerMock->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValue($configProviderMock));
    }
}
