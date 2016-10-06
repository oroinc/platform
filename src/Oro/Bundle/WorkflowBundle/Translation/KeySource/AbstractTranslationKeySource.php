<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeySource;

use Oro\Bundle\WorkflowBundle\Model\Workflow;

abstract class AbstractTranslationKeySource implements TranslationKeySourceInterface
{
    /** @var array */
    protected $data;

    /**
     * @param Workflow $workflow
     * @param array $data
     */
    public function __construct(Workflow $workflow, array $data = [])
    {
        $data = array_merge(['workflow_name' => $workflow->getName()], $data);
        $requiredKeys = $this->getRequiredKeys();

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data) || empty($data[$key])) {
                throw new \InvalidArgumentException(
                    sprintf('Expected not empty value for key "%s" in data, null given', $key)
                );
            }
        }

        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    abstract protected function getRequiredKeys();
}
