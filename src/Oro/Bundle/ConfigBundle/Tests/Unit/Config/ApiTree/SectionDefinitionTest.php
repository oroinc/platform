<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config\ApiTree;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;

class SectionDefinitionTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $name = 'test';
        $section = new SectionDefinition($name);
        self::assertEquals($name, $section->getName());
    }

    public function testVariables(): void
    {
        $section = new SectionDefinition('test');
        self::assertEquals([], $section->getVariables());

        $variable = new VariableDefinition('test_variable', 'string');
        $section->addVariable($variable);
        self::assertEquals([$variable], $section->getVariables());
        self::assertSame($variable, $section->getVariable('test_variable'));
        self::assertNull($section->getVariable('another_variable'));
    }

    public function testSubSection(): void
    {
        $section = new SectionDefinition('test');
        self::assertEquals([], $section->getSubSections());

        $subSection = new SectionDefinition('test_section');
        $section->addSubSection($subSection);
        self::assertEquals([$subSection], $section->getSubSections());
        self::assertSame($subSection, $section->getSubSection('test_section'));
        self::assertNull($section->getSubSection('another_section'));
    }
}
