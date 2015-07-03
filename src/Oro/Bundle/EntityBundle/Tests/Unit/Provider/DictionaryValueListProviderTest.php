<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\DictionaryValueListProvider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class DictionaryValueListProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var   DictionaryValueListProvider */
    protected $dictionaryValueListProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $typeHelper;

    protected function setUp()
    {
        $this->configManager        = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->extendConfigProvider);

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->typeHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dictionaryValueListProvider = new DictionaryValueListProvider(
            $this->configManager,
            $this->doctrine,
            $this->typeHelper
        );
    }

    public function testGetValueListQueryBuilder()
    {
        $className = 'Test\Dictionary';

        $qb   = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with($className)
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->assertSame(
            $qb,
            $this->dictionaryValueListProvider->getValueListQueryBuilder($className)
        );
    }

    public function testGetSerializationConfig()
    {
        $className = 'Test\Dictionary';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($metadata);
        $metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'default', 'extend_field']);

        $this->extendConfigProvider->expects($this->exactly(4))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [
                        $className,
                        'id',
                        $this->getEntityFieldConfig($className, 'id', [])
                    ],
                    [
                        $className,
                        'name',
                        $this->getEntityFieldConfig($className, 'name', [])
                    ],
                    [
                        $className,
                        'default',
                        $this->getEntityFieldConfig($className, 'default', [])
                    ],
                    [
                        $className,
                        'extend_field',
                        $this->getEntityFieldConfig($className, 'extend_field', ['is_extend' => true])
                    ],
                ]
            );

        $this->assertEquals(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'       => null,
                    'name'     => null,
                    'default'  => null
                ]
            ],
            $this->dictionaryValueListProvider->getSerializationConfig($className)
        );
    }

    /**
     * @param string $className
     * @param mixed  $values
     *
     * @return Config
     */
    protected function getEntityConfig($className, $values)
    {
        $configId = new EntityConfigId('extend', $className);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param mixed  $values
     *
     * @return Config
     */
    protected function getEntityFieldConfig($className, $fieldName, $values)
    {
        $configId = new FieldConfigId('extend', $className, $fieldName);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }
}
