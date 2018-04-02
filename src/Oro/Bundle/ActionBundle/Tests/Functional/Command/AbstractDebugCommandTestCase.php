<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;

abstract class AbstractDebugCommandTestCase extends WebTestCase
{
    /** @var FactoryWithTypesInterface */
    protected $factory;

    protected function setUp()
    {
        $this->initClient();
        $this->factory = $this->getContainer()->get($this->getFactoryServiceId());
    }

    public function testExecute()
    {
        $typeNames = array_keys($this->factory->getTypes());
        $result = $this->runCommand($this->getCommandName());
        $this->assertContains('Short Description', $result);
        foreach ($typeNames as $name) {
            $this->assertContains($name, $result);
        }
    }

    public function testExecuteWithArgument()
    {
        $types = $this->factory->getTypes();
        $typeNames = array_keys($types);
        $name = array_shift($typeNames);
        $result = $this->runCommand($this->getCommandName(), [$name]);
        $this->assertContains('Full Description', $result);
        $this->assertContains($name, $result);
        $this->assertContains(array_shift($types), $result);
    }

    public function testExecuteWithNotExistsArgument()
    {
        $name = 'some_not_exists_name';
        $result = $this->runCommand($this->getCommandName(), [$name]);

        $this->assertEquals(sprintf('Type "%s" is not found', $name), $result);
    }

    /**
     * @return string
     */
    abstract protected function getFactoryServiceId();

    /**
     * @return string
     */
    abstract protected function getCommandName();
}
