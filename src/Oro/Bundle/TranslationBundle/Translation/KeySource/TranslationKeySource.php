<?php

namespace Oro\Bundle\TranslationBundle\Translation\KeySource;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeySourceInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

/**
 * Translation key source that generates translation keys from templates and data.
 *
 * Implements the translation key source interface to provide translation key generation
 * based on a template and associated data. Requires all necessary data to be provided
 * at construction time and validates that all required keys from the template are present
 * and non-empty before allowing the source to be used.
 */
class TranslationKeySource implements TranslationKeySourceInterface
{
    /**
     * @var TranslationKeyTemplateInterface
     */
    private $keyTemplate;

    /**
     * @var array
     */
    private $data;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(TranslationKeyTemplateInterface $keyTemplate, array $data = [])
    {
        $this->keyTemplate = $keyTemplate;
        $this->data = $data;

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
    }

    #[\Override]
    public function getTemplate()
    {
        return $this->keyTemplate->getTemplate();
    }

    #[\Override]
    public function getData()
    {
        return $this->data;
    }
}
