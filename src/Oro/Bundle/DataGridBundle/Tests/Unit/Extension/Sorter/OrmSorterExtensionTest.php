<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;

class OrmSorterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $input
     * @param array $expected
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $extension = new OrmSorterExtension();
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
                    OrmSorterExtension::SORTERS_ROOT_PARAM => array(
                        'firstName' => OrmSorterExtension::DIRECTION_ASC,
                        'lastName'  => OrmSorterExtension::DIRECTION_DESC,
                    )
                ),
                'expected' => array(
                    OrmSorterExtension::SORTERS_ROOT_PARAM => array(
                        'firstName' => OrmSorterExtension::DIRECTION_ASC,
                        'lastName'  => OrmSorterExtension::DIRECTION_DESC,
                    )
                )
            ),
            'minified' => array(
                'input' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        OrmSorterExtension::MINIFIED_SORTERS_PARAM => array(
                            'firstName' => '-1',
                            'lastName'  => '1',
                        )
                    )
                ),
                'expected' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        OrmSorterExtension::MINIFIED_SORTERS_PARAM => array(
                            'firstName' => '-1',
                            'lastName'  => '1',
                        )
                    ),
                    OrmSorterExtension::SORTERS_ROOT_PARAM => array(
                        'firstName' => OrmSorterExtension::DIRECTION_ASC,
                        'lastName'  => OrmSorterExtension::DIRECTION_DESC,
                    )
                )
            ),
        );
    }
}
