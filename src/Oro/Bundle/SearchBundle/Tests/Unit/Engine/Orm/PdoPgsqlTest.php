<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql;

class PdoPgsqlTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPlainSql()
    {
        $recordString = PdoPgsql::getPlainSql('oro_search_index_text');
        $this->assertTrue(strpos($recordString, 'to_tsvector') > 0);
        $this->assertContains('oro_search_index_text', $recordString);
    }

    public function testInitRepo()
    {
        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config->expects($this->any())
            ->method('addCustomStringFunction');

        $om = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $om->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($config));

        $classMetadata = $this
            ->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $driver = new PdoPgsql();
        $driver->initRepo($om, $classMetadata);
    }
}
