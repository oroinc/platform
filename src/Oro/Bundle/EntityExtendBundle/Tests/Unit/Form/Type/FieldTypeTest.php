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

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class FieldTypeTest extends TypeTestCase
{
    const FIELDS_GROUP    = 'oro.entity_extend.form.data_type_group.fields';
    const RELATIONS_GROUP = 'oro.entity_extend.form.data_type_group.relations';

    /** @var  FieldType $type */
    protected $type;

    /** @var  \ReflectionClass */
    protected $typeReflection;

    /** @var  ConfigManager */
    protected $configManagerMock;

    /** @var  Translator */
    protected $translatorMock;

    protected $defaultFieldTypeChoices = [
        self::FIELDS_GROUP    => [
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
        self::RELATIONS_GROUP => [
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

        $this->translatorMock = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translatorMock->expects($this->any())
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

    public function testName()
    {
        $this->assertEquals('oro_entity_extend_field_type', $this->type->getName());
    }

    public function testType()
    {
        $configProviderMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $entityConfigMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfigMock->expects($this->once())
            ->method('is')
            ->with('relation')
            ->will($this->returnValue(false));

        $entityConfigMock->expects($this->never())
            ->method('get');

        $this->configManagerMock->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValue($configProviderMock));

        $configProviderMock->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($entityConfigMock));

        $form = $this->factory->create($this->type, null, $this->formOptions);

        $this->assertSame(
            $this->defaultFieldTypeChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'name', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeWithAssignedRelations()
    {
        $this->prepareTestTypeWithRelations($this->prepareRelationsConfig());

        $form = $this->factory->create($this->type, null, $this->formOptions);

        $expectedChoices = $this->defaultFieldTypeChoices;
        $typeName        = 'oneToMany|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel1'
            . '||testentity1_rel1';

        $expectedChoices[self::RELATIONS_GROUP] = array_merge(
            $expectedChoices[self::RELATIONS_GROUP],
            [$typeName => 'oro.entity_extend.form.data_type.inverse_relation']
        );
        $this->assertSame(
            $expectedChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'name', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeWithUnAssignedRelations()
    {
        $config    = $this->prepareRelationsConfig();
        $configKey = 'oneToMany|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel1';

        $config['relationConfig'][$configKey]['assign'] = false;

        $this->prepareTestTypeWithRelations($config, false);

        $form = $this->factory->create($this->type, null, $this->formOptions);

        $this->assertSame(
            $this->defaultFieldTypeChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'name', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    protected function prepareRelationsConfig()
    {
        $relationConfigFieldId       = new FieldConfigId(
            'extend',
            'Oro\Bundle\UserBundle\Entity\User',
            'testentity1_rel1',
            'manyToOne'
        );
        $relationTargetConfigFieldId = new FieldConfigId(
            'extend',
            'Extend\Entity\testEntity1',
            'rel1',
            'oneToMany'
        );

        $relationConfig = [
            'oneToMany|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel1' => [
                'assign'          => 1,
                'field_id'        => $relationConfigFieldId,
                'owner'           => 1,
                'target_entity'   => 'Extend\Entity\testEntity1',
                'target_field_id' => $relationTargetConfigFieldId
            ]
        ];

        return [
            'relationTargetConfigFieldId' => $relationTargetConfigFieldId,
            'relationConfig'              => $relationConfig
        ];
    }

    protected function prepareTestTypeWithRelations($config = [], $withAssigned = true)
    {
        $entityConfigMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfigMock->expects($this->at(0))
            ->method('is')
            ->with('relation')
            ->will($this->returnValue(true));
        $entityConfigMock->expects($this->at(1))
            ->method('get')
            ->with('relation')
            ->will($this->returnValue($config['relationConfig']));

        if ($withAssigned) {
            $entityConfigMock->expects($this->at(2))
                ->method('get')
                ->with('label')
                ->will($this->returnValue('labelValue'));
        }

        $configProviderMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configProviderMock->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($entityConfigMock));
        $configProviderMock->expects($this->any())
            ->method('getConfigById')
            ->with($config['relationTargetConfigFieldId'])
            ->will($this->returnValue($entityConfigMock));

        $this->configManagerMock->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValue($configProviderMock));
    }
}
