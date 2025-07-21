<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ActionGroupDefinitionTest extends TestCase
{
    use EntityTestCaseTrait;

    private ActionGroupDefinition $actionGroupDefinition;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionGroupDefinition = new ActionGroupDefinition();
    }

    public function testGettersAndSetters(): void
    {
        self::assertPropertyAccessors(
            $this->actionGroupDefinition,
            [
                ['name', 'test'],
                ['actions', ['config1', 'config2'], []],
                ['conditions', ['config1', 'config2'], []],
                ['parameters', ['config1', 'config2'], []],
            ]
        );
    }
}
