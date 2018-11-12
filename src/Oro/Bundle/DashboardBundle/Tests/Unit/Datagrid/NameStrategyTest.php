<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DashboardBundle\Datagrid\NameStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class NameStrategyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NameStrategy
     */
    protected $nameStrategy;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    public function setUp()
    {
        $this->requestStack = new RequestStack();
        $this->nameStrategy = new NameStrategy($this->requestStack);
    }

    public function testGetGridUniqueNameShouldReturnOriginalNameIfRequestIsNull()
    {
        $name = 'name';

        $uniqueName = $this->nameStrategy->getGridUniqueName($name);

        $this->assertEquals($name, $uniqueName);
    }

    public function testGetGridShouldReturnNameSuffixedWithWidgetIdIfCurrentRequestIsRelatedWithWidget()
    {
        $request = new Request([
            '_widgetId' => 5,
        ]);
        $this->requestStack->push($request);
        $uniqueName = $this->nameStrategy->getGridUniqueName('name');

        $this->assertEquals('name_w5', $uniqueName);
    }
}
