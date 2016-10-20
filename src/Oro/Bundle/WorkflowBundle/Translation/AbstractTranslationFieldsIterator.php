<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

abstract class AbstractTranslationFieldsIterator implements TranslationFieldsIteratorInterface
{
    /** @var TranslationKeyTemplateInterface[] */
    protected $templateInstances;

    /** @var TranslationKeyGenerator */
    protected $keyGenerator;

    /** @var mixed */
    private $currentValue;

    /** @var bool */
    private $gotNewValue = false;

    /**
     * @param mixed $value
     */
    public function writeCurrent($value)
    {
        $this->currentValue = $value;
        $this->gotNewValue = true;
    }

    /**
     * @return bool
     */
    protected function hasChanges()
    {
        return $this->gotNewValue;
    }

    /**
     * @return void
     */
    protected function clear()
    {
        $this->currentValue = null;
        $this->gotNewValue = false;
    }

    /**
     * Returns current modification value, erases it from memory.
     * @return mixed
     */
    protected function pickChangedValue()
    {
        $value = $this->currentValue;
        $this->clear();

        return $value;
    }

    /**
     * @return TranslationKeyGenerator
     */
    protected function getKeyGenerator()
    {
        if ($this->keyGenerator) {
            return $this->keyGenerator;
        }

        return $this->keyGenerator = new TranslationKeyGenerator();
    }

    /**
     * @param $templateClass
     * @return TranslationKeyTemplateInterface
     * @throws \InvalidArgumentException
     */
    protected function getTemplate($templateClass)
    {
        if (array_key_exists($templateClass, $this->templateInstances)) {
            return $this->templateInstances[$templateClass];
        }

        if (!is_a($templateClass, TranslationKeyTemplateInterface::class, true)) {
            throw new \InvalidArgumentException(
                sprintf('Template class must implement %s', TranslationKeyTemplateInterface::class)
            );
        }

        return $this->templateInstances[$templateClass] = new $templateClass;
    }

    /**
     * @param string $templateClass
     * @param \ArrayObject $context
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function makeKey($templateClass, \ArrayObject $context)
    {
        return $this->getKeyGenerator()->generate($this->makeSource($templateClass, $context));
    }

    /**
     * @param $templateClass
     * @param \ArrayObject $context
     * @return TranslationKeySource
     * @throws \InvalidArgumentException
     */
    protected function makeSource($templateClass, \ArrayObject $context)
    {
        return new TranslationKeySource($this->getTemplate($templateClass), $context->getArrayCopy());
    }
}
