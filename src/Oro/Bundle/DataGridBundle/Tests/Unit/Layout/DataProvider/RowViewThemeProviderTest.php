<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\DataGridBundle\Layout\DataProvider\RowViewThemeProvider;

class RowViewThemeProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DataGridThemeHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeHelper;

    /**
     * @var RowViewThemeProvider
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->themeHelper = $this->createMock(DataGridThemeHelper::class);
        $this->provider = new RowViewThemeProvider($this->themeHelper);
    }

    public function testGetThemeByGridName()
    {
        $theme = 'tests';
        $dataGridName = 'test_datagrid';
        $this->themeHelper->expects($this->once())
            ->method('getTheme')
            ->with($dataGridName)
            ->willReturn($theme);
        $this->assertEquals($theme, $this->provider->getThemeByGridName($dataGridName));
    }
}
