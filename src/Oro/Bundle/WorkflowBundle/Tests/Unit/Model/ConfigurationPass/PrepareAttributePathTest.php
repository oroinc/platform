<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\ConfigurationPass;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\ConfigurationPass\PrepareAttributePath;

class PrepareAttributePathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param Collection $attributes
     * @param array $expected
     */
    public function testPassConfiguration($configuration, $attributes, $expected)
    {
        $pass = new PrepareAttributePath();
        $pass->setAttributes($attributes);
        $this->assertEquals($expected, $pass->passConfiguration($configuration));
    }

    /**
     * @return array
     */
    public function configurationDataProvider()
    {
        return array(
            array(
                array(
                    'test' => array(
                        'opt1' => 'test',
                        'opt2' => array(
                            'test1' => '$no_path_attr',
                            'test2' => '$attr',
                            'test3' => '$attr.name.full'
                        ),
                        'opt3' => '$attr'
                    )
                ),
                new ArrayCollection(
                    array(
                        'no_path_attr' => $this->createAttribute('no_path_attr'),
                        'attr' => $this->createAttribute('attr', 'test')
                    )
                ),
                array(
                    'test' => array(
                        'opt1' => 'test',
                        'opt2' => array(
                            'test1' => '$no_path_attr',
                            'test2' => '$test',
                            'test3' => '$test.name.full'
                        ),
                        'opt3' => '$test'
                    )
                ),
            ),
        );
    }

    /**
     * @param string $name
     * @param null|string $propertyPath
     * @return Attribute
     */
    protected function createAttribute($name, $propertyPath = null)
    {
        $attr = new Attribute();
        $attr->setName($name)
            ->setPropertyPath($propertyPath);
        return $attr;
    }
}
