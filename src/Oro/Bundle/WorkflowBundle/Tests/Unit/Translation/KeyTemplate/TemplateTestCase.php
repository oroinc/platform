<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\TranslationKeyTemplateInterface;

abstract class TemplateTestCase extends \PHPUnit_Framework_TestCase
{
    /** @return TranslationKeyTemplateInterface */
    abstract public function getTemplateInstance();

    abstract public function testGetTemplate();

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
}
