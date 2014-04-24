<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ChartBundle\Form\Type\ChartType;

class ChartTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ChartSettingsType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->configProvider = $this
            ->getMockBuilder('\Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ChartType($this->configProvider);

        parent::setUp();
    }
}
