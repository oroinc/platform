<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;

class OrmFilterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $input
     * @param array $expected
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $extension = new OrmFilterExtension($translator);
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
                    OrmFilterExtension::FILTER_ROOT_PARAM => array(
                        'firstName' => array('value' => 'John'),
                    ),
                ),
                'expected' => array(
                    OrmFilterExtension::FILTER_ROOT_PARAM => array(
                        'firstName' => array('value' => 'John'),
                    ),
                )
            ),
            'minified' => array(
                'input' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        OrmFilterExtension::MINIFIED_FILTER_PARAM => array(
                            'firstName' => array('value' => 'John'),
                        ),
                    )
                ),
                'expected' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        OrmFilterExtension::MINIFIED_FILTER_PARAM => array(
                            'firstName' => array('value' => 'John'),
                        ),
                    ),
                    OrmFilterExtension::FILTER_ROOT_PARAM => array(
                        'firstName' => array('value' => 'John'),
                    ),
                )
            ),
        );
    }
}
