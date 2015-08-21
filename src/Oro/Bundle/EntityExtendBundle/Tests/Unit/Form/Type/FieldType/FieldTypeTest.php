<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type\FieldType;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Exception\RuntimeException;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type\FieldTypeTest as BaseFieldTypeTest;

/**
 * Test to simulate bug with no fieldConfigModel exists for reverse relation
 * This won't fail if the bug will be fixed, it's just to demonstrate
 */
class FieldTypeTest extends BaseFieldTypeTest
{
    protected $formOptions = [
        'class_name' => 'OroCRM\Bundle\ContactBundle\Entity\Contact'
    ];

    /**
     * Test that FieldType provide correct choices for field type select
     * correct choices for owner side (e.g. Contact) - only standard o2m, m2o, m2m
     */
    public function testOneToManyRelation()
    {
        // mock expecting an exception, but it shouldn't happen
        $this->prepareTestType($this->prepareOneToManyRelationsConfig(), true);

        $form = $this->factory->create($this->type, null, $this->formOptions);

        $this->assertSame(
            $this->defaultFieldTypeChoices[self::RELATIONS_GROUP],
            $form->offsetGet('type')->getConfig()->getOption('choices')[self::RELATIONS_GROUP],
            'Failed: asserting that relation choices are the same'
        );
    }

    /**
     * Test that FieldType provide correct choices for field type select
     * correct choices for reverse side (e.g. ConctactAddress) - standard + reverse
     */
    public function testManyToOneRelation()
    {
        // assert that reverse relation added on ContactAddress choices
        $this->prepareTestType($this->prepareManyToOneRelationsConfig());
        $form = $this->factory->create($this->type, null, $this->formOptions);

        $typeName = 'oneToMany|OroCRM\Bundle\ContactBundle\Entity\Contact|' .
            'OroCRM\Bundle\ContactBundle\Entity\ContactAddress|addresses||contact_addresses';

        $expectedChoices = $this->defaultFieldTypeChoices;
        $expectedChoices[self::RELATIONS_GROUP] = array_merge(
            $expectedChoices[self::RELATIONS_GROUP],
            [$typeName => 'oro.entity_extend.form.data_type.inverse_relation']
        );
        $this->assertSame(
            $expectedChoices[self::RELATIONS_GROUP],
            $form->offsetGet('type')->getConfig()->getOption('choices')[self::RELATIONS_GROUP]
        );
    }

    /**
     * @param array      $config
     * @param bool|false $withException
     */
    protected function prepareTestType($config = [], $withException = false)
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

        if (!$withException) {
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

        if ($withException) {
            $configProviderMock->expects($this->any())
                ->method('getConfigById')
                ->with($config['relationTargetConfigFieldId'])
                ->will($this->throwException(
                    new RuntimeException(
                        sprintf(
                            'A model for "%s" was not found',
                            'OroCRM\Bundle\ContactBundle\Entity\ContactAddress::contact_addresses'
                        )
                    )
                ));
        } else {
            $configProviderMock->expects($this->any())
                ->method('getConfigById')
                ->with($config['relationTargetConfigFieldId'])
                ->will($this->returnValue($entityConfigMock));
        }

        $this->configManagerMock->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValue($configProviderMock));
    }

    /**
     * from Contact to ContactAddress
     *
     * @return array
     */
    protected function prepareOneToManyRelationsConfig()
    {
        $relationConfigFieldId       = new FieldConfigId(
            'extend',
            'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'addresses',
            'oneToMany'
        );
        $relationTargetConfigFieldId = new FieldConfigId(
            'extend',
            'OroCRM\Bundle\ContactBundle\Entity\ContactAddress',
            'contact_addresses',
            'manyToOne'
        );

        $relationConfig = [
            implode('|', [
                'oneToMany',
                'OroCRM\Bundle\ContactBundle\Entity\Contact',
                'OroCRM\Bundle\ContactBundle\Entity\ContactAddress',
                'addresses'
            ]) => [
                'assign'          => true,
                'field_id'        => $relationConfigFieldId,
                'owner'           => false,
                'target_entity'   => 'OroCRM\Bundle\ContactBundle\Entity\ContactAddress',
                'target_field_id' => $relationTargetConfigFieldId
            ]
        ];

        return [
            'relationTargetConfigFieldId' => $relationTargetConfigFieldId,
            'relationConfig'              => $relationConfig
        ];
    }

    /**
     * from ContactAddress to Contact
     *
     * @return array
     * @SuppressWarnings(PHPMD.)
     */
    protected function prepareManyToOneRelationsConfig()
    {
        $relationConfigFieldId       = new FieldConfigId(
            'extend',
            'OroCRM\Bundle\ContactBundle\Entity\ContactAddress',
            'contact_addresses',
            'manyToOne'
        );
        $relationTargetConfigFieldId = new FieldConfigId(
            'extend',
            'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'addresses',
            'oneToMany'
        );

        $relationConfig = [
            implode('|', [
                'oneToMany',
                'OroCRM\Bundle\ContactBundle\Entity\Contact',
                'OroCRM\Bundle\ContactBundle\Entity\ContactAddress',
                'addresses'
            ]) => [
                'assign'          => true,
                'field_id'        => $relationConfigFieldId,
                'owner'           => true,
                'target_entity'   => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
                'target_field_id' => $relationTargetConfigFieldId
            ]
        ];

        return [
            'relationTargetConfigFieldId' => $relationTargetConfigFieldId,
            'relationConfig'              => $relationConfig
        ];
    }
}
