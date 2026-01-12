<?php

namespace Oro\Bundle\TranslationBundle\Translation\KeySource;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeySourceInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

/**
 * Dynamic translation key source that allows configuration after instantiation.
 *
 * Implements the translation key source interface with support for deferred configuration.
 * Allows setting a key template and data after object creation through the configure() method,
 * enabling flexible construction patterns. Validates that all required keys from the template
 * are present in the provided data before allowing use.
 */
class DynamicTranslationKeySource implements TranslationKeySourceInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var TranslationKeyTemplateInterface
     */
    private $keyTemplate;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param TranslationKeyTemplateInterface $keyTemplate
     * @param array|null $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function configure(TranslationKeyTemplateInterface $keyTemplate, ?array $data = null)
    {
        $this->keyTemplate = $keyTemplate;

        if (null !== $data) {
            $this->data = array_merge($this->data, $data);
        }

        foreach ($keyTemplate->getRequiredKeys() as $key) {
            if (!array_key_exists($key, $this->data) || empty($this->data[$key])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected not empty value for key "%s" in data, null given for template %s',
                        $key,
                        get_class($keyTemplate)
                    )
                );
            }
        }

        return $this;
    }

    /**
     * @return TranslationKeyTemplateInterface
     * @throws \LogicException
     */
    protected function getKeyTemplate()
    {
        if (null === $this->keyTemplate) {
            throw new \LogicException(
                'Can\'t build source without template. Please configure source by ->configure($template) method.'
            );
        }

        return $this->keyTemplate;
    }

    /**
     * @return string
     * @throws \LogicException
     */
    #[\Override]
    public function getTemplate()
    {
        return $this->getKeyTemplate()->getTemplate();
    }

    /**
     * @return array
     */
    #[\Override]
    public function getData()
    {
        return $this->data;
    }
}
