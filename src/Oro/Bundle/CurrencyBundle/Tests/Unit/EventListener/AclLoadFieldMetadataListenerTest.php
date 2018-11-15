<?php

namespace Oro\Bundle\CurrencyBundle\Unit\EventListener;

use Oro\Bundle\CurrencyBundle\EventListener\AclLoadFieldMetadataListener;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\SecurityBundle\Event\LoadFieldsMetadata;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

class AclLoadFieldMetadataListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnLoad()
    {
        $className = 'Acme\Test';
        $fields = [
            'field0' => new FieldSecurityMetadata('field0', 'field0Label'), // regular field
            'field1' => new FieldSecurityMetadata('field1', 'field1Label'),
            'field2' => new FieldSecurityMetadata('field2', 'field2Label'),
            'field3' => new FieldSecurityMetadata('field3', 'field3Label')
        ];
        $event = new LoadFieldsMetadata($className, $fields);

        /** @var \PHPUnit\Framework\MockObject\MockObject $multicurrencyProvider */
        $multicurrencyProvider = $this->createMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider');
        $multicurrencyProvider->expects($this->once())
            ->method('filter')
            ->willReturn([
                new Config(
                    new FieldConfigId('multicurrency', $className, 'field1', 'currency'),
                    [
                        'target' => 'targetField'
                    ]
                ),
                new Config(
                    new FieldConfigId('multicurrency', $className, 'field2', 'money_type'),
                    [
                        'target'        => 'targetField',
                        'virtual_field' => 'virtualField'
                    ]
                ),
                new Config(
                    new FieldConfigId('multicurrency', $className, 'field3', 'money'),
                    [
                        'target' => 'targetField'
                    ]
                )
            ]);

        /** @var \PHPUnit\Framework\MockObject\MockObject $entityProvider */
        $entityProvider = $this->createMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider');
        $entityProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, 'field2')
            ->willReturn(
                new Config(
                    new FieldConfigId('multicurrency', $className, 'field2', 'money_type'),
                    [
                        'label' => 'field2Label'
                    ]
                )
            );

        /** @var \PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\ConfigManager');
        $configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->withConsecutive(['multicurrency'], ['entity'])
            ->willReturnOnConsecutiveCalls($multicurrencyProvider, $entityProvider);

        $aclLoadFieldMetadataListener = new AclLoadFieldMetadataListener($configManager);
        $aclLoadFieldMetadataListener->onLoad($event);

        $this->assertEquals(
            [
                // field0 should stay as is
                'field0'      => new FieldSecurityMetadata('field0', 'field0Label'),
                // field1 should become hidden, alias should be set
                'field1'      => new FieldSecurityMetadata('field1', 'field1Label', [], null, 'targetField', true),
                // field2 should become hidden, alias should be set
                'field2'      => new FieldSecurityMetadata('field2', 'field2Label', [], null, 'targetField', true),
                // field3 should become hidden, alias should be set
                'field3'      => new FieldSecurityMetadata('field3', 'field3Label', [], null, 'targetField', true),
                // new FieldSecurityMetadata should be added for target field
                'targetField' => new FieldSecurityMetadata('targetField', 'field2Label', ['VIEW', 'CREATE', 'EDIT']),
            ],
            $event->getFields()
        );
    }
}
