<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;
use PHPUnit\Framework\TestCase;

abstract class TemplateTestCase extends TestCase
{
    /** @return TranslationKeyTemplateInterface */
    abstract public function getTemplateInstance();

    /**
     * @return void
     */
    abstract public function testGetTemplate();

    /**
     * @return void
     */
    abstract public function testGetRequiredKeys();

    /** @param string $templateString */
    protected function assertTemplate($templateString)
    {
        $this->assertSame($templateString, $this->getTemplateInstance()->getTemplate());
    }

    /** @param array $requiredKeys */
    protected function assertRequiredKeys(array $requiredKeys)
    {
        $this->assertSame($requiredKeys, $this->getTemplateInstance()->getRequiredKeys());
    }

    /**
     * @param string $name
     */
    protected function assertName($name)
    {
        $this->assertSame($name, $this->getTemplateInstance()->getName());
    }

    protected function assertKeyTemplates(array $templates)
    {
        $this->assertEquals($templates, $this->getTemplateInstance()->getKeyTemplates());
    }
}
