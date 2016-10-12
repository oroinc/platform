<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeySource;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeySourceInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;

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
     * @param TranslationKeyTemplateInterface $keyTemplate
     * @param array $data
     * @throws \InvalidArgumentException
     */
    public function __construct(TranslationKeyTemplateInterface $keyTemplate, array $data = [])
    {
        $this->keyTemplate = $keyTemplate;
        $this->data = $data;

        foreach ($keyTemplate->getRequiredKeys() as $key) {
            if (!array_key_exists($key, $this->data) || empty($this->data[$key])) {
                throw new \InvalidArgumentException(
                    sprintf('Expected not empty value for key "%s" in data, null given', $key)
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->keyTemplate->getTemplate();
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }
}
