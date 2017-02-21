<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;

class GlobalScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var GlobalScopeManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $repo;

    /** @var CacheProvider */
    protected $cache;

    protected function setUp()
    {
        $this->em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repo = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('Oro\Bundle\ConfigBundle\Entity\Config')
            ->willReturn($this->repo);

        $doctrine    = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = new ArrayCache();

        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\ConfigBundle\Entity\Config')
            ->willReturn($this->em);

        $this->manager = new GlobalScopeManager($doctrine, $this->cache);
    }

    /**
     * Test get loaded settings
     */
    public function testGetLoaded()
    {
        $config       = new Config();
        $configValue1 = new ConfigValue();
        $configValue1
            ->setSection('oro_user')
            ->setName('level')
            ->setValue(2000)
            ->setType('scalar');
        $config->getValues()->add($configValue1);

        $this->repo->expects($this->once())
            ->method('findByEntity')
            ->with('app', 0)
            ->will($this->returnValue($config));

        $this->assertFalse($this->cache->contains('app_0'));
        $this->assertEquals(
            $configValue1->getValue(),
            $this->manager->getSettingValue('oro_user.level')
        );
        $this->assertEquals($this->cache->fetch('app_0'), $this->getCachedConfig($config));

        $this->assertNull($this->manager->getSettingValue('oro_user.greeting'));
        $this->assertNull($this->manager->getSettingValue('oro_test.nosetting'));
        $this->assertNull($this->manager->getSettingValue('noservice.nosetting'));
    }

    /**
     * Test get info from loaded settings
     */
    public function testGetInfoLoaded()
    {
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));

        $config       = new Config();
        $configValue1 = new ConfigValue();
        $configValue1
            ->setSection('oro_user')
            ->setName('level')
            ->setValue(2000)
            ->setType('scalar')
            ->setUpdatedAt($datetime);
        $class = new \ReflectionClass($configValue1);
        $prop  = $class->getProperty('createdAt');
        $prop->setAccessible(true);
        $prop->setValue($configValue1, $datetime);
        $config->getValues()->add($configValue1);

        $this->repo->expects($this->once())
            ->method('findByEntity')
            ->with('app', 0)
            ->will($this->returnValue($config));

        $this->assertFalse($this->cache->contains('app_0'));
        list($created, $updated, $isNullValue) = $this->manager->getInfo('oro_user.level');
        $this->assertEquals($this->cache->fetch('app_0'), $this->getCachedConfig($config));

        $this->assertEquals($configValue1->getCreatedAt(), $created);
        $this->assertEquals($configValue1->getUpdatedAt(), $updated);
        $this->assertFalse($isNullValue);
    }

    /**
     * Test saving settings
     */
    public function testSave()
    {
        $config       = new Config();
        $configValue1 = new ConfigValue();
        $configValue1
            ->setSection('oro_user')
            ->setName('update')
            ->setValue('old value')
            ->setType('scalar');
        $configValue2 = new ConfigValue();
        $configValue2
            ->setSection('oro_user')
            ->setName('remove')
            ->setValue('test')
            ->setType('scalar');
        $config->getValues()->add($configValue1);
        $config->getValues()->add($configValue2);

        $settings = [
            'oro_user.update' => [
                'value'                  => 'updated value',
                'use_parent_scope_value' => false
            ],
            'oro_user.remove' => [
                'use_parent_scope_value' => true
            ],
            'oro_user.add'    => [
                'value'                  => 'new value',
                'use_parent_scope_value' => false
            ],
        ];

        $this->repo->expects($this->any())
            ->method('findByEntity')
            ->with('app', 0)
            ->will($this->returnValue($config));

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($config));
        $this->em->expects($this->once())
            ->method('flush');

        $this->assertFalse($this->cache->contains('app_0'));
        $result = $this->manager->save($settings);
        $this->assertEquals(
            [
                [
                    'oro_user.update' => 'updated value',
                    'oro_user.add'    => 'new value'
                ],
                [
                    'oro_user.remove'
                ]
            ],
            $result
        );

        $this->assertEquals($this->cache->fetch('app_0'), $this->getCachedConfig($config));
        $this->assertEquals('updated value', $this->manager->getSettingValue('oro_user.update'));
        $this->assertNull($this->manager->getSettingValue('oro_user.remove'));
        $this->assertEquals('new value', $this->manager->getSettingValue('oro_user.add'));
    }

    /**
     * @param Config $config
     *
     * @return array
     */
    private function getCachedConfig(Config $config)
    {
        $cachedConfig = [];

        foreach ($config->getValues() as $configValue) {
            if (isset($cachedConfig[$configValue->getSection()])) {
                $cachedConfig[$configValue->getSection()][$configValue->getName()] = [
                    'value' => $configValue->getValue(),
                    'use_parent_scope_value' => false,
                    'createdAt' => $configValue->getCreatedAt(),
                    'updatedAt' => $configValue->getUpdatedAt()
                ];
            } else {
                $cachedConfig[$configValue->getSection()] = [
                    $configValue->getName() => [
                        'value' => $configValue->getValue(),
                        'use_parent_scope_value' => false,
                        'createdAt' => $configValue->getCreatedAt(),
                        'updatedAt' => $configValue->getUpdatedAt()
                    ]
                ];
            }
        }

        return $cachedConfig;
    }
}
