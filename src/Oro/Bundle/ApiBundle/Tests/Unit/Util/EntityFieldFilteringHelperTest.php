<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\EntityFieldFilteringHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityFieldFilteringHelperTest extends TestCase
{
    private const string TEST_CLASS_NAME = 'Test\Entity';

    private ConfigManager&MockObject $configManager;
    private EntityFieldFilteringHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->helper = new EntityFieldFilteringHelper($this->configManager);
    }

    private function getEntityConfig(array $config): ConfigInterface
    {
        return new Config(
            new EntityConfigId('extend', self::TEST_CLASS_NAME),
            $config
        );
    }

    private function getFieldConfig(string $fieldName, array $config): ConfigInterface
    {
        return new Config(
            new FieldConfigId('extend', self::TEST_CLASS_NAME, $fieldName, 'integer'),
            $config
        );
    }

    public function testFilterEntityFieldsForUnspecifiedExclusionPolicy(): void
    {
        $this->configManager->expects(self::never())
            ->method('hasConfig');
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $filteredFieldNames = $this->helper->filterEntityFields(
            self::TEST_CLASS_NAME,
            ['field1', 'field2', 'field3'],
            ['field1'],
            null
        );
        self::assertEquals(['field1', 'field2', 'field3'], $filteredFieldNames);
    }

    public function testFilterEntityFieldsForNoneExclusionPolicy(): void
    {
        $this->configManager->expects(self::never())
            ->method('hasConfig');
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $filteredFieldNames = $this->helper->filterEntityFields(
            self::TEST_CLASS_NAME,
            ['field1', 'field2', 'field3'],
            ['field1'],
            ConfigUtil::EXCLUSION_POLICY_NONE
        );
        self::assertEquals(['field1', 'field2', 'field3'], $filteredFieldNames);
    }

    public function testFilterEntityFieldsForAllExclusionPolicy(): void
    {
        $this->configManager->expects(self::never())
            ->method('hasConfig');
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $filteredFieldNames = $this->helper->filterEntityFields(
            self::TEST_CLASS_NAME,
            ['field1', 'field2', 'field3'],
            ['field1'],
            ConfigUtil::EXCLUSION_POLICY_ALL
        );
        self::assertEquals(['field1'], $filteredFieldNames);
    }

    public function testFilterEntityFieldsForCustomFieldsExclusionPolicyForFieldsOfNonConfigurableEntity(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $filteredFieldNames = $this->helper->filterEntityFields(
            self::TEST_CLASS_NAME,
            ['field1', 'field2', 'field3'],
            ['field1'],
            ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS
        );
        self::assertEquals(['field1', 'field2', 'field3'], $filteredFieldNames);
    }

    public function testFilterEntityFieldsForCustomFieldsExclusionPolicyForFieldsOfCustomEntity(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
            );
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $filteredFieldNames = $this->helper->filterEntityFields(
            self::TEST_CLASS_NAME,
            ['field1', 'field2', 'field3'],
            ['field1'],
            ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS
        );
        self::assertEquals(['field1', 'field2', 'field3'], $filteredFieldNames);
    }

    public function testFilterEntityFieldsForCustomFieldsExclusionPolicyForFieldsOfExtendSystemEntity(): void
    {
        $this->configManager->expects(self::exactly(3))
            ->method('hasConfig')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, null, true],
                [self::TEST_CLASS_NAME, 'field2', true],
                [self::TEST_CLASS_NAME, 'field3', true]
            ]);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'field2',
                    $this->getFieldConfig('field2', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
                ],
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'field3',
                    $this->getFieldConfig('field3', ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
                ]
            ]);

        $filteredFieldNames = $this->helper->filterEntityFields(
            self::TEST_CLASS_NAME,
            ['field1', 'field2', 'field3'],
            ['field1'],
            ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS
        );
        self::assertEquals(['field1', 'field2'], $filteredFieldNames);
    }

    public function testIsExtendSystemEntityForNonConfigurableEntity(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertFalse($this->helper->isExtendSystemEntity(self::TEST_CLASS_NAME));
    }

    public function testIsExtendSystemEntityForNonExtendedEntity(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => false])
            );
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertFalse($this->helper->isExtendSystemEntity(self::TEST_CLASS_NAME));
    }

    public function testIsExtendSystemEntityForSystemOwnedExtendedEntity(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertTrue($this->helper->isExtendSystemEntity(self::TEST_CLASS_NAME));
    }

    public function testIsExtendSystemEntityForCustomOwnedExtendedEntity(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
            );
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertFalse($this->helper->isExtendSystemEntity(self::TEST_CLASS_NAME));
    }

    public function testIsCustomFieldForNonConfigurableEntity(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertFalse($this->helper->isCustomField(self::TEST_CLASS_NAME, 'field1'));
    }

    public function testIsCustomFieldForNotExtendedField(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::TEST_CLASS_NAME, 'field1')
            ->willReturn(
                $this->getFieldConfig('field1', ['is_extend' => false])
            );

        self::assertFalse($this->helper->isCustomField(self::TEST_CLASS_NAME, 'field1'));
    }

    public function testIsCustomFieldForSystemOwnedExtendedField(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::TEST_CLASS_NAME, 'field1')
            ->willReturn(
                $this->getFieldConfig('field1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );

        self::assertFalse($this->helper->isCustomField(self::TEST_CLASS_NAME, 'field1'));
    }

    public function testIsCustomFieldForCustomOwnedExtendedField(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::TEST_CLASS_NAME, 'field1')
            ->willReturn(
                $this->getFieldConfig('field1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
            );

        self::assertTrue($this->helper->isCustomField(self::TEST_CLASS_NAME, 'field1'));
    }

    public function testIsCustomAssociationForNonConfigurableEntity(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME, 'association1')
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertFalse($this->helper->isCustomAssociation(self::TEST_CLASS_NAME, 'association1'));
    }

    public function testIsCustomAssociationForNotExtendedAssociation(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME, 'association1')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::TEST_CLASS_NAME, 'association1')
            ->willReturn(
                $this->getFieldConfig('association1', ['is_extend' => false])
            );

        self::assertFalse($this->helper->isCustomAssociation(self::TEST_CLASS_NAME, 'association1'));
    }

    public function testIsCustomAssociationForSystemOwnedExtendedAssociation(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME, 'association1')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::TEST_CLASS_NAME, 'association1')
            ->willReturn(
                $this->getFieldConfig('association1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );

        self::assertFalse($this->helper->isCustomAssociation(self::TEST_CLASS_NAME, 'association1'));
    }

    public function testIsCustomAssociationForCustomOwnedExtendedAssociation(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME, 'association1')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::TEST_CLASS_NAME, 'association1')
            ->willReturn(
                $this->getFieldConfig('association1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
            );

        self::assertTrue($this->helper->isCustomAssociation(self::TEST_CLASS_NAME, 'association1'));
    }

    public function testIsCustomAssociationForDefaultAssociationAndNonConfigurableEntity(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->withConsecutive(
                [self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                [self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertFalse($this->helper->isCustomAssociation(
            self::TEST_CLASS_NAME,
            ExtendConfigDumper::DEFAULT_PREFIX . 'association1'
        ));
    }

    public function testIsCustomAssociationForNotExtendedDefaultAssociation(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->withConsecutive(
                [self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                [self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->withConsecutive(
                ['extend', self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                ['extend', self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturnOnConsecutiveCalls(
                $this->getFieldConfig(ExtendConfigDumper::DEFAULT_PREFIX . 'association1', ['is_extend' => false]),
                $this->getFieldConfig('association1', ['is_extend' => false])
            );

        self::assertFalse($this->helper->isCustomAssociation(
            self::TEST_CLASS_NAME,
            ExtendConfigDumper::DEFAULT_PREFIX . 'association1'
        ));
    }

    public function testIsCustomAssociationForSystemOwnedExtendedDefaultAssociation(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->withConsecutive(
                [self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                [self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->withConsecutive(
                ['extend', self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                ['extend', self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturnOnConsecutiveCalls(
                $this->getFieldConfig(
                    ExtendConfigDumper::DEFAULT_PREFIX . 'association1',
                    ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM]
                ),
                $this->getFieldConfig('association1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );

        self::assertFalse($this->helper->isCustomAssociation(
            self::TEST_CLASS_NAME,
            ExtendConfigDumper::DEFAULT_PREFIX . 'association1'
        ));
    }

    public function testIsCustomAssociationForCustomOwnedExtendedDefaultAssociation(): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1')
            ->willReturn(
                $this->getFieldConfig(
                    ExtendConfigDumper::DEFAULT_PREFIX . 'association1',
                    ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
                )
            );

        self::assertTrue($this->helper->isCustomAssociation(
            self::TEST_CLASS_NAME,
            ExtendConfigDumper::DEFAULT_PREFIX . 'association1'
        ));
    }

    public function testIsCustomAssociationForSystemOwnedExtendedDefaultAssociationButCustomOwnedAssociation(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->withConsecutive(
                [self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                [self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->withConsecutive(
                ['extend', self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                ['extend', self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturnOnConsecutiveCalls(
                $this->getFieldConfig(
                    ExtendConfigDumper::DEFAULT_PREFIX . 'association1',
                    ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM]
                ),
                $this->getFieldConfig('association1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
            );

        self::assertTrue($this->helper->isCustomAssociation(
            self::TEST_CLASS_NAME,
            ExtendConfigDumper::DEFAULT_PREFIX . 'association1'
        ));
    }

    public function testIsCustomAssociationForNotExtendedDefaultAssociationAndSystemOwnedAssociation(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->withConsecutive(
                [self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                [self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->withConsecutive(
                ['extend', self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                ['extend', self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturnOnConsecutiveCalls(
                $this->getFieldConfig(ExtendConfigDumper::DEFAULT_PREFIX . 'association1', ['is_extend' => false]),
                $this->getFieldConfig('association1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );

        self::assertFalse($this->helper->isCustomAssociation(
            self::TEST_CLASS_NAME,
            ExtendConfigDumper::DEFAULT_PREFIX . 'association1'
        ));
    }

    public function testIsCustomAssociationForNotExtendedDefaultAssociationAndCustomOwnedAssociation(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->withConsecutive(
                [self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                [self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->withConsecutive(
                ['extend', self::TEST_CLASS_NAME, ExtendConfigDumper::DEFAULT_PREFIX . 'association1'],
                ['extend', self::TEST_CLASS_NAME, 'association1']
            )
            ->willReturnOnConsecutiveCalls(
                $this->getFieldConfig(ExtendConfigDumper::DEFAULT_PREFIX . 'association1', ['is_extend' => false]),
                $this->getFieldConfig('association1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
            );

        self::assertTrue($this->helper->isCustomAssociation(
            self::TEST_CLASS_NAME,
            ExtendConfigDumper::DEFAULT_PREFIX . 'association1'
        ));
    }
}
