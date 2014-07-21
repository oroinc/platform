<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;

class ContentProviderManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentProviderManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->manager = new ContentProviderManager();
    }

    public function testProviderAddGet()
    {
        $this->assertCount(0, $this->manager->getContentProviders());
        $this->assertFalse($this->manager->hasContentProvider('test1'));

        $testContentProviderOne = $this->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface')
            ->setMethods(array('setEnabled', 'isEnabled', 'getName'))
            ->getMockForAbstractClass();
        $testContentProviderOne->expects($this->once())
            ->method('setEnabled')
            ->with(true);
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('test1'));

        $testContentProviderTwo = $this->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface')
            ->setMethods(array('setEnabled', 'isEnabled', 'getName'))
            ->getMockForAbstractClass();
        $testContentProviderTwo->expects($this->once())
            ->method('setEnabled')
            ->with(false);
        $testContentProviderTwo->expects($this->atLeastOnce())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $testContentProviderTwo->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('test2'));

        $this->manager->addContentProvider($testContentProviderOne, true);
        $this->manager->addContentProvider($testContentProviderTwo, false);

        $this->assertCount(2, $this->manager->getContentProviders());
        $this->assertTrue($this->manager->hasContentProvider('test1'));
        $this->assertTrue($this->manager->hasContentProvider('test2'));
        $this->assertFalse($this->manager->hasContentProvider('test3'));

        $enabledProcessors = $this->manager->getEnabledContentProviders();
        $this->assertCount(1, $enabledProcessors);
        $this->assertEquals($testContentProviderOne, $enabledProcessors->first());

        $processorsByKey = $this->manager->getContentProvidersByKeys(array('test2'));
        $this->assertCount(1, $processorsByKey);
        $this->assertEquals($testContentProviderTwo, $processorsByKey->first());
    }

    public function testDisableProcessor()
    {
        $testContentProviderOne = $this->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface')
            ->setMethods(array('setEnabled', 'isEnabled', 'getName'))
            ->getMockForAbstractClass();
        $testContentProviderOne->expects($this->exactly(2))
            ->method('setEnabled');
        $testContentProviderOne->expects($this->at(0))
            ->method('setEnabled')
            ->with(true);
        $testContentProviderOne->expects($this->at(2))
            ->method('setEnabled')
            ->with(false);
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('test1'));

        $this->manager->addContentProvider($testContentProviderOne, true);
        $this->manager->disableContentProvider('test1');
    }

    public function testEnableProcessor()
    {
        $testContentProviderOne = $this->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface')
            ->setMethods(array('setEnabled', 'isEnabled', 'getName'))
            ->getMockForAbstractClass();
        $testContentProviderOne->expects($this->exactly(2))
            ->method('setEnabled');
        $testContentProviderOne->expects($this->at(0))
            ->method('setEnabled')
            ->with(false);
        $testContentProviderOne->expects($this->at(2))
            ->method('setEnabled')
            ->with(true);
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('test1'));

        $this->manager->addContentProvider($testContentProviderOne, false);
        $this->manager->enableContentProvider('test1');
    }

    public function testGetContentEnabled()
    {
        $testContentProviderOne = $this->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface')
            ->setMethods(array('setEnabled', 'isEnabled', 'getName', 'getContent'))
            ->getMockForAbstractClass();
        $testContentProviderOne->expects($this->once())
            ->method('setEnabled')
            ->with(true);
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('test1'));
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('getContent')
            ->will($this->returnValue('test1_content'));

        $expected = array('test1' => 'test1_content');
        $this->manager->addContentProvider($testContentProviderOne, true);
        $this->assertEquals($expected, $this->manager->getContent());
    }

    public function testGetContentKeys()
    {
        $testContentProviderOne = $this->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface')
            ->setMethods(array('setEnabled', 'isEnabled', 'getName', 'getContent'))
            ->getMockForAbstractClass();
        $testContentProviderOne->expects($this->once())
            ->method('setEnabled')
            ->with(true);
        $testContentProviderOne->expects($this->never())
            ->method('isEnabled');
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('test1'));
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('getContent')
            ->will($this->returnValue('test1_content'));

        $expected = array('test1' => 'test1_content');
        $this->manager->addContentProvider($testContentProviderOne, true);
        $this->assertEquals($expected, $this->manager->getContent(array('test1')));
    }
}
