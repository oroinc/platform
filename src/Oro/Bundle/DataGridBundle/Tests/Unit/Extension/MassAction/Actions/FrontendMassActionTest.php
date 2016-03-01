<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\FrontendMassAction;

class FrontendMassActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var FrontendMassAction */
    protected $action;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->action = new FrontendMassAction();
    }

    public function testSetOptions()
    {
        $options = [
            'frontend_type' => 'frontend-mass',
        ];
        $this->action->setOptions(ActionConfiguration::create($options));
        $this->assertEquals($options, $this->action->getOptions()->toArray());
    }
}
