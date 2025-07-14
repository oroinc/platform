<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TranslationsDatagridRouteHelperTest extends TestCase
{
    private DatagridRouteHelper&MockObject $datagridRouteHelper;
    private TranslationsDatagridRouteHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->datagridRouteHelper = $this->createMock(DatagridRouteHelper::class);

        $this->helper = new TranslationsDatagridRouteHelper($this->datagridRouteHelper);
    }

    public function testGenerate(): void
    {
        $this->datagridRouteHelper->expects($this->once())
            ->method('generate')
            ->with(
                TranslationsDatagridRouteHelper::TRANSLATION_GRID_ROUTE_NAME,
                TranslationsDatagridRouteHelper::TRANSLATION_GRID_NAME,
                ['f' => ['filterName' => ['value' => '10', 'type' => '20']]],
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn('generatedValue');

        $this->assertEquals('generatedValue', $this->helper->generate(['filterName' => 10], 1, ['filterName' => 20]));
    }
}
