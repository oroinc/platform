<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

class ConfigRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigRepository
     */
    protected $repository;

    /**
     * @var EntityManager
     */
    protected $om;

    /**
     * prepare mocks
     */
    public function setUp()
    {
        $this->om = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array())
            ->getMock();

        $this->repository = $this->getMock(
            'Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository',
            array('findOneBy'),
            array(
                $this->om,
                new ClassMetadata('Oro\Bundle\ConfigBundle\Entity\Config')
            )
        );
    }

    /**
     * data provider for loadSettings test
     */
    public function loadSettingsProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * test loadSettings
     *
     * @dataProvider loadSettingsProvider
     */
    public function testLoadSettings($isScope)
    {
        $criteria = array(
            'scopedEntity' => 'user',
            'recordId' => 1,
        );

        if ($isScope) {
            $value = $this->getMock('Oro\Bundle\ConfigBundle\Entity\ConfigValue');
            $value->expects($this->once())
                ->method('getSection')
                ->will($this->returnValue('oro_user'));
            $value->expects($this->once())
                ->method('getName')
                ->will($this->returnValue('level'));
            $value->expects($this->once())
                ->method('getValue')
                ->will($this->returnValue('test'));

            $scope = $this->getMock('Oro\Bundle\ConfigBundle\Entity\Config');
            $scope->expects($this->once())
                ->method('getValues')
                ->will($this->returnValue(array($value)));
            $scope->expects($this->once())
                ->method('getEntity')
                ->will($this->returnValue('user'));

            $this->repository
                ->expects($this->once())
                ->method('findOneBy')
                ->with($criteria)
                ->will($this->returnValue($scope));
        } else {
            $this->repository
                ->expects($this->once())
                ->method('findOneBy')
                ->with($criteria)
                ->will($this->returnValue(false));
        }

        $settings = $this->repository->loadSettings(
            $criteria['scopedEntity'],
            $criteria['recordId']
        );

        if ($isScope) {
            $this->assertArrayHasKey('oro_user', $settings);
            $this->assertEquals('test', $settings['oro_user']['level']['value']);
        } else {
            $this->assertEmpty($settings);
        }
    }

    /**
     * Test getByEntity method
     */
    public function testGetByEntity()
    {
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue(false));

        $this->repository->getByEntity('app', 0);
    }
}
