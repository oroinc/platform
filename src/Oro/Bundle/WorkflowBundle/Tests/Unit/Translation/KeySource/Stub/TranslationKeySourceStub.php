<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeySource\Stub;

use Oro\Bundle\WorkflowBundle\Translation\KeySource\AbstractTranslationKeySource;

class TranslationKeySourceStub extends AbstractTranslationKeySource
{
    /** @var array */
    static public $requiredKeys = [];

    /** @var string */
    static public $template = '';

    /**
     * {@inheritdoc}
     */
    protected function getRequiredKeys()
    {
        return static::$requiredKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return static::$template;
    }
}
