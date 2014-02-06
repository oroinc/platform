<?php
/**
 * Created by PhpStorm.
 * User: Alexandr
 * Date: 2/6/14
 * Time: 1:06 PM
 */

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit;


use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\Actions\Merge\MergeMassAction;

class MergeMassActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeMassAction $target;
     */
    private $target;

    public function setup()
    {
        $this->target = new MergeMassAction();
    }

    public function testMergeMassActionSetOptionShouldAddDefaultRouteOptionIfItIsEmpty()
    {
        $this->target->setOptions(ActionConfiguration::create(array('class_name'=>1)));
        $options  = $this->target->getOptions();
        $this->assertEquals($options['route'], 'oro_entity_merge_test');
    }
    public function testMergeMassActionSetOptionShouldAddDefaultIPropertyNameOptionIfItIsEmpty()
    {
        $this->target->setOptions(ActionConfiguration::create(array('class_name'=>1)));
        $options  = $this->target->getOptions();
        $this->assertEquals($options['id_property_name'], 'id');
    }
    public function testMergeMassActionSetOptionShouldAddDefaultMaxElementCountOptionIfItIsEmpty()
    {
        $this->target->setOptions(ActionConfiguration::create(array('class_name'=>1)));
        $options  = $this->target->getOptions();
        $this->assertEquals($options['max_element_count'], '5');
    }
    public function testMergeMassActionSetOptionShouldThrowExceptionIfClassNameOptionIsEmpty()
    {
        $this->setExpectedException('Exception');
        $this->target->setOptions(ActionConfiguration::create(array()));
    }
}
