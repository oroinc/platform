<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfiguration;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\ItemValue;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTargetInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;

class PermissionConfigurationProviderTest extends TestCase
{
    use TempDirExtension;

    private const PERMISSION1 = 'PERMISSION1';
    private const PERMISSION2 = 'PERMISSION2';
    private const PERMISSION3 = 'PERMISSION3';

    private array $permissions = [
        self::PERMISSION1 => [
            'label' => 'Label for Permission 1',
            'group_names' => ['frontend'],
            'apply_to_all' => true,
            'apply_to_entities' => [],
            'exclude_entities' => [],
            'apply_to_interfaces' => [],
        ],
        self::PERMISSION2 => [
            'label' => 'Label for Permission 2',
            'group_names' => [PermissionConfiguration::DEFAULT_GROUP_NAME, 'frontend', 'new_group'],
            'apply_to_all' => false,
            'apply_to_entities' => [
                TestActivity::class,
                Product::class,
                TestActivityTarget::class,
            ],
            'exclude_entities' => [
                Item::class,
                ItemValue::class,
                WorkflowAwareEntity::class,
            ],
            'description' => 'Permission 2 description',
            'apply_to_interfaces' => [
                TestActivityInterface::class,
                TestActivityTargetInterface::class,
            ],
        ],
        self::PERMISSION3 => [
            'label' => 'Label for Permission 3',
            'group_names' => ['default'],
            'apply_to_all' => true,
            'apply_to_entities' => ['NotManageableEntity'],
            'exclude_entities' => [],
            'apply_to_interfaces' => [],
        ],
    ];

    private PermissionConfigurationProvider $provider;

    protected function setUp(): void
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $cacheFile = $this->getTempFile('PermissionConfigurationProvider');

        $this->provider = new PermissionConfigurationProvider($cacheFile, false);
    }

    public function testCorrectConfiguration()
    {
        $expectedPermissions = [
            self::PERMISSION1 => $this->permissions[self::PERMISSION1],
            self::PERMISSION2 => $this->permissions[self::PERMISSION2],
            self::PERMISSION3 => $this->permissions[self::PERMISSION3],
        ];

        $permissions = $this->provider->getPermissionConfiguration();
        $this->assertEquals($expectedPermissions, $permissions);
    }

    public function testFilterPermissionsConfiguration()
    {
        $expectedPermissions = [
            self::PERMISSION1 => $this->permissions[self::PERMISSION1],
            self::PERMISSION3 => $this->permissions[self::PERMISSION3],
        ];

        $permissions = $this->provider->getPermissionConfiguration(array_keys($expectedPermissions));
        $this->assertEquals($expectedPermissions, $permissions);
    }
}
