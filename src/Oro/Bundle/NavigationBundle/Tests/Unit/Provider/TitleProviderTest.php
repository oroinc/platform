<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\NavigationBundle\Entity\Title;
use Oro\Bundle\NavigationBundle\Provider\TitleProvider;

class TitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var TitleProvider */
    protected $titleProvider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->titleProvider  = new TitleProvider($this->doctrineHelper);
    }

    public function testGetTitleTemplatesFromDatabase()
    {
        $routeName = 'test_route';

        $title = new Title();
        $title->setTitle('db_title');
        $title->setShortTitle('db_short_title');

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Oro\Bundle\NavigationBundle\Entity\Title')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['route' => $routeName])
            ->will($this->returnValue($title));

        $result = $this->titleProvider->getTitleTemplates($routeName);
        $this->assertEquals(
            ['title' => $title->getTitle(), 'short_title' => $title->getShortTitle()],
            $result
        );

        // test that loaded titles are cached
        $this->titleProvider->getTitleTemplates($routeName);
    }

    public function testGetTitleTemplatesFromConfig()
    {
        $routeName   = 'test_route';
        $configTitle = 'Test config title';

        $this->titleProvider->setTitles(
            [$routeName => $configTitle]
        );

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Oro\Bundle\NavigationBundle\Entity\Title')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['route' => $routeName])
            ->will($this->returnValue(null));

        $result = $this->titleProvider->getTitleTemplates($routeName);
        $this->assertEquals(
            ['title' => $configTitle, 'short_title' => $configTitle],
            $result
        );

        // test that loaded titles are cached
        $this->titleProvider->getTitleTemplates($routeName);
    }

    public function testGetTitleTemplatesShouldReturnEmptyArrayIfTemplatesDoNotExist()
    {
        $routeName = 'test_route';

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Oro\Bundle\NavigationBundle\Entity\Title')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['route' => $routeName])
            ->will($this->returnValue(null));

        $result = $this->titleProvider->getTitleTemplates($routeName);
        $this->assertEquals(
            [],
            $result
        );

        // test that loaded titles are cached
        $this->titleProvider->getTitleTemplates($routeName);
    }
}
