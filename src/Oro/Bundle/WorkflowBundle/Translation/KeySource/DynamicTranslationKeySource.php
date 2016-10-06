<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeySource;

use Oro\Bundle\WorkflowBundle\Translation\TranslationKeySourceInterface;
use Oro\Bundle\WorkflowBundle\Translation\TranslationKeyTemplateInterface;

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

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param TranslationKeyTemplateInterface $keyTemplate
     * @param array|null $data
     * @throws \InvalidArgumentException
     */
    public function configure(TranslationKeyTemplateInterface $keyTemplate, array $data = null)
    {
        $this->keyTemplate = $keyTemplate;

        if (null !== $data) {
            $this->data = array_merge($this->data, $data);
        }

        foreach ($keyTemplate->getRequiredKeys() as $key) {
            if (!array_key_exists($key, $this->data) || empty($this->data[$key])) {
                throw new \InvalidArgumentException(
                    sprintf('Expected not empty value for key "%s" in data, null given', $key)
                );
            }
        }
    }

    /**
     * @return TranslationKeyTemplateInterface
     * @throws \LogicException
     */
    protected function getKeyTemplate()
    {
        if (null === $this->keyTemplate) {
            throw new \LogicException(
                'Cant build source without template'
            );
        }

        return $this->keyTemplate;
    }

    /**
     * @return string
     * @throws \LogicException
     */
    public function getTemplate()
    {
        return $this->getKeyTemplate()->getTemplate();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
