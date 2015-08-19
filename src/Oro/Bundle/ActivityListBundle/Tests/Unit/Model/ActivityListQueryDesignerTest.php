<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Model;

use Oro\Bundle\ActivityListBundle\Model\ActivityListQueryDesigner;

class ActivityListQueryDesignerTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersAndSetter()
    {
        $queryDesigner = new ActivityListQueryDesigner();
        $queryDesigner->setDefinition('definition');
        $queryDesigner->setEntity('entity');

        $this->assertEquals('definition', $queryDesigner->getDefinition());
        $this->assertEquals('entity', $queryDesigner->getEntity());
    }
}
