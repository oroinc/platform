<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Datagrid;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DashboardBundle\Datagrid\NameStrategy;

class NameStrategyTest extends \PHPUnit_Framework_TestCase
{
    protected $nameStrategy;

    public function setUp()
    {
        $this->nameStrategy = new NameStrategy();
    }

    public function testGetGridUniqueNameShouldReturnOriginalNameIfRequestIsNull()
    {
        $name = 'name';

        $this->nameStrategy->setRequest();
        $uniqueName = $this->nameStrategy->getGridUniqueName($name);

        $this->assertEquals($name, $uniqueName);
    }

    public function testGetGridShouldReturnNameSuffixedWithWidgetIdIfCurrentRequestIsRelatedWithWidget()
    {
        $request = new Request([
            '_widgetId' => 5,
        ]);
        $this->nameStrategy->setRequest($request);
        $uniqueName = $this->nameStrategy->getGridUniqueName('name');

        $this->assertEquals('name_w5', $uniqueName);
    }
}
