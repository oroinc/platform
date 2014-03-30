<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Forms;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;

use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;

use Oro\Bundle\TranslationBundle\Translation\Translator;

class FieldTypeTest extends \PHPUnit_Framework_TestCase
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

        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->getFormFactory();

        $this->configManagerMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translatorMock = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type           = new FieldType($this->configManagerMock, $this->translatorMock);
        $this->typeReflection = new \ReflectionClass($this->type);
    }

    public function testFieldTypes()
    {
        $fieldTypeProperties = $this->typeReflection->getDefaultProperties();
        $this->assertEquals($this->defaultFieldTypesKeys, array_keys($fieldTypeProperties['types']));
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
        $entityConfigMock
            ->expects($this->once())
            ->method('is')
            ->with('relation')
            ->will($this->returnValue(false));

        $entityConfigMock
            ->expects($this->exactly(0))
            ->method('get');

        $this->configManagerMock
            ->expects($this->exactly(2))
            ->method('getProvider')
            ->will($this->returnValue($configProviderMock));

        $configProviderMock
            ->expects($this->exactly(1))
            ->method('getConfig')
            ->will($this->returnValue($entityConfigMock));

        $form = $this->factory->create($this->type, null, $this->formOptions);

        $this->assertEquals(
            $this->defaultFieldTypesKeys,
            array_keys($form->offsetGet('type')->getConfig()->getOption('choices'))
        );

        $form->submit(['fieldName' => 'name', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeWithAssignedRelations()
    {
        $this->prepareTestTypeWithRelations($this->prepareRelationsConfig());

        $form = $this->factory->create($this->type, null, $this->formOptions);

        $this->assertEquals(
            array_merge(
                $this->defaultFieldTypesKeys,
                ['oneToMany|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel1||testentity1_rel1']
            ),
            array_keys($form->offsetGet('type')->getConfig()->getOption('choices'))
        );

        $form->submit(['fieldName' => 'name', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeWithUnAssignedRelations()
    {
        $config = $this->prepareRelationsConfig();
        $configKey = 'oneToMany|Extend\Entity\testEntity1|Oro\Bundle\UserBundle\Entity\User|rel1';

        $config['relationConfig'][$configKey]['assign'] = false;

        $this->prepareTestTypeWithRelations($config, false);

        $form = $this->factory->create($this->type, null, $this->formOptions);

        $this->assertEquals(
            $this->defaultFieldTypesKeys,
            array_keys($form->offsetGet('type')->getConfig()->getOption('choices'))
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
            'relationConfigFieldId'       => $relationConfigFieldId,
            'relationTargetConfigFieldId' => $relationTargetConfigFieldId,
            'relationConfig'              => $relationConfig
        ];
    }

    protected function prepareTestTypeWithRelations($config = [], $withAssigned = true)
    {
        $entityConfigMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfigMock
            ->expects($this->at(0))
            ->method('is')
            ->with('relation')
            ->will($this->returnValue(true));
        $entityConfigMock
            ->expects($this->at(1))
            ->method('get')
            ->with('relation')
            ->will($this->returnValue($config['relationConfig']));

        if ($withAssigned) {
            $entityConfigMock
                ->expects($this->at(2))
                ->method('get')
                ->with('label')
                ->will($this->returnValue('labelValue'));
        }

        $configProviderMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configProviderMock
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($entityConfigMock));
        $configProviderMock
            ->expects($this->any())
            ->method('getConfigById')
            ->with($config['relationTargetConfigFieldId'])
            ->will($this->returnValue($entityConfigMock));

        $this->configManagerMock
            ->expects($this->exactly(2))
            ->method('getProvider')
            ->will($this->returnValue($configProviderMock));
    }
}
