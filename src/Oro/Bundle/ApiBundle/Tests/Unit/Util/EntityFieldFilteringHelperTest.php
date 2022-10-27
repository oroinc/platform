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

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityFieldFilteringHelperTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_CLASS_NAME = 'Test\Entity';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityFieldFilteringHelper */
    private $helper;

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
            new FieldConfigId('extend', self::TEST_CLASS_NAME, $fieldName, 'int'),
            $config
        );
    }

    public function testFilterEntityFieldsForUnspecifiedExclusionPolicy()
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

    public function testFilterEntityFieldsForNoneExclusionPolicy()
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

    public function testFilterEntityFieldsForAllExclusionPolicy()
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

    public function testFilterEntityFieldsForCustomFieldsExclusionPolicyForFieldsOfNonConfigurableEntity()
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

    public function testFilterEntityFieldsForCustomFieldsExclusionPolicyForFieldsOfCustomEntity()
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

    public function testFilterEntityFieldsForCustomFieldsExclusionPolicyForFieldsOfExtendSystemEntity()
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

    public function testIsExtendSystemEntityForNonConfigurableEntity()
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

    public function testIsExtendSystemEntityForNonExtendedEntity()
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

    public function testIsExtendSystemEntityForSystemOwnedExtendedEntity()
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

    public function testIsExtendSystemEntityForCustomOwnedExtendedEntity()
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

    public function testIsCustomFieldForNonConfigurableEntity()
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

    public function testIsCustomFieldForNotExtendedField()
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

    public function testIsCustomFieldForSystemOwnedExtendedField()
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

    public function testIsCustomFieldForCustomOwnedExtendedField()
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

    public function testIsCustomAssociationForNonConfigurableEntity()
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

    public function testIsCustomAssociationForNotExtendedAssociation()
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

    public function testIsCustomAssociationForSystemOwnedExtendedAssociation()
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

    public function testIsCustomAssociationForCustomOwnedExtendedAssociation()
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

    public function testIsCustomAssociationForDefaultAssociationAndNonConfigurableEntity()
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

    public function testIsCustomAssociationForNotExtendedDefaultAssociation()
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

    public function testIsCustomAssociationForSystemOwnedExtendedDefaultAssociation()
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

    public function testIsCustomAssociationForCustomOwnedExtendedDefaultAssociation()
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

    public function testIsCustomAssociationForSystemOwnedExtendedDefaultAssociationButCustomOwnedAssociation()
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

    public function testIsCustomAssociationForNotExtendedDefaultAssociationAndSystemOwnedAssociation()
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

    public function testIsCustomAssociationForNotExtendedDefaultAssociationAndCustomOwnedAssociation()
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
