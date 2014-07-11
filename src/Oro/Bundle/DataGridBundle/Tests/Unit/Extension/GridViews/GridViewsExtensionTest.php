<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\GridViews;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;

class GridViewsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $input
     * @param array $expected
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $extension = new GridViewsExtension();
        $extension->setParameters(new ParameterBag($input));
        $this->assertEquals($expected, $extension->getParameters()->all());
    }

    /**
     * @return array
     */
    public function setParametersDataProvider()
    {
        return array(
            'empty' => array(
                'input' => array(),
                'expected' => array(),
            ),
            'regular' => array(
                'input' => array(
                    ParameterBag::ADDITIONAL_PARAMETERS => array(
                        GridViewsExtension::VIEWS_PARAM_KEY => 'view'
                    )
                ),
                'expected' => array(
                    ParameterBag::ADDITIONAL_PARAMETERS => array(
                        GridViewsExtension::VIEWS_PARAM_KEY => 'view'
                    )
                )
            ),
            'minified' => array(
                'input' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        GridViewsExtension::MINIFIED_VIEWS_PARAM_KEY => 'view'
                    )
                ),
                'expected' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        GridViewsExtension::MINIFIED_VIEWS_PARAM_KEY => 'view'
                    ),
                    ParameterBag::ADDITIONAL_PARAMETERS => array(
                        GridViewsExtension::VIEWS_PARAM_KEY => 'view'
                    )
                )
            ),
        );
    }
}
