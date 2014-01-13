<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Cache;

class EntityCacheClearerTest extends \PHPUnit_Framework_TestCase
{
    public function testClear()
    {
        $clearer = $this->getMockBuilder('Oro\Bundle\EmailBundle\Cache\EntityCacheClearer')
            ->setConstructorArgs(array('SomeDir', 'Test%sProxy'))
            ->setMethods(array('createFilesystem'))
            ->getMock();

        $fs = $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        $clearer->expects($this->once())
            ->method('createFilesystem')
            ->will($this->returnValue($fs));

        $fs->expects($this->at(0))
            ->method('remove')
            ->with($this->equalTo('SomeDir' . DIRECTORY_SEPARATOR . 'TestEmailAddressProxy.php'));
        $fs->expects($this->at(1))
            ->method('remove')
            ->with($this->equalTo('SomeDir' . DIRECTORY_SEPARATOR . 'TestEmailAddressProxy.orm.yml'));

        $clearer->clear('');
    }
}
