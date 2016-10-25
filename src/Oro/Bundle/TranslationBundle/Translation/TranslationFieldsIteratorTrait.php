<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;

trait TranslationFieldsIteratorTrait
{
    /** @var TranslationKeyTemplateInterface[] */
    protected $templateInstances = [];

    /** @var TranslationKeyGenerator */
    protected $keyGenerator;

    /** @var mixed */
    private $currentModificationValue;

    /** @var bool */
    private $gotNewValue = false;

    /**
     * @param mixed $value
     */
    public function writeCurrent($value)
    {
        $this->currentModificationValue = $value;
        $this->gotNewValue = true;
    }

    /**
     * Whether current value was pointing for change by ->writeCurrent($newValue) method
     * @return bool
     */
    protected function hasChanges()
    {
        return $this->gotNewValue;
    }

    /**
     * Clearing modification of current value for next iterator step
     * @return void
     */
    protected function clear()
    {
        $this->currentModificationValue = null;
        $this->gotNewValue = false;
    }

    /**
     * Returns current modification value, erases it from memory.
     * @return mixed
     */
    protected function pickChangedValue()
    {
        $value = $this->currentModificationValue;
        $this->clear();

        return $value;
    }

    /**
     * Returns current modification value
     * @return mixed
     */
    protected function getChangedValue()
    {
        return $this->currentModificationValue;
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
     * Cached instances factory
     * @param string $templateClass
     * @return TranslationKeyTemplateInterface
     * @throws \InvalidArgumentException
     */
    protected function getTemplate($templateClass)
    {
        if (!array_key_exists($templateClass, $this->templateInstances)) {
            if (!is_a($templateClass, TranslationKeyTemplateInterface::class, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Template class must implement %s', TranslationKeyTemplateInterface::class)
                );
            }

            $this->templateInstances[$templateClass] = new $templateClass;
        }

        return $this->templateInstances[$templateClass];
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
     * Factory for Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource
     * @param string $templateClass
     * @param \ArrayObject $context
     * @return TranslationKeySource
     * @throws \InvalidArgumentException
     */
    protected function makeSource($templateClass, \ArrayObject $context)
    {
        return new TranslationKeySource($this->getTemplate($templateClass), $context->getArrayCopy());
    }
}
