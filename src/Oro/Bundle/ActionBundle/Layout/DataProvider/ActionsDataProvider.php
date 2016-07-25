<?php

namespace Oro\Bundle\ActionBundle\Layout\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationManager;

use Oro\Component\Layout\ContextInterface;

class ActionsDataProvider
{
    /**
     * @var OperationManager
     */
    protected $operationManager;

    /**
     * @var ContextHelper
     */
    protected $contextHelper;

    /**
     * @var RestrictHelper
     */
    protected $restrictHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @param OperationManager $operationManager
     * @param ContextHelper $contextHelper
     * @param RestrictHelper $restrictHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        OperationManager $operationManager,
        ContextHelper $contextHelper,
        RestrictHelper $restrictHelper,
        TranslatorInterface $translator
    ) {
        $this->operationManager = $operationManager;
        $this->contextHelper = $contextHelper;
        $this->restrictHelper = $restrictHelper;
        $this->translator = $translator;
    }

    /**
     * @param string $var
     * @return mixed
     */
    public function __get($var)
    {
        if (strpos($var, 'group') === 0) {
            $groups = explode('And', str_replace('group', '', $var));
            foreach ($groups as &$group) {
                $group = preg_replace('/(?<=[a-zA-Z])(?=[A-Z])/', '_', $group);
                $groupNameParts =  array_map('strtolower', explode('_', $group));
                $group = implode('_', $groupNameParts);
            }

            return $this->getByGroup($groups);
        } else {
            throw new \RuntimeException('Property ' . $var . ' is unknown');
        }
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->getByGroup();
    }

    /**
     * @return array
     */
    public function getWithoutGroup()
    {
        return $this->getByGroup(false);
    }

    /**
     * @param array|null|bool|string $groups
     * @return array
     */
    public function getByGroup($groups = null)
    {
        if ($this->context && $this->context->data()->has('entity')) {
            $context = $this->contextHelper->getActionParameters(['entity' => $this->context->data()->get('entity')]);
        } else {
            $context = null;
        }

        $actions = $this->restrictHelper->restrictOperationsByGroup(
            $this->operationManager->getOperations($context),
            $groups
        );

        return $this->getPreparedData($actions);
    }

    /**
     * @param Operation[] $operations
     * @return array
     */
    protected function getPreparedData(array $operations = [])
    {
        $data = [];
        foreach ($operations as $operation) {
            if (!$operation->getDefinition()->isEnabled()) {
                continue;
            }

            $definition = $operation->getDefinition();

            $frontendOptions = $definition->getFrontendOptions();
            $buttonOptions = $definition->getButtonOptions();
            if (!empty($frontendOptions['title'])) {
                $title = $frontendOptions['title'];
            } else {
                $title = $definition->getLabel();
            }
            $icon = !empty($buttonOptions['icon']) ? $buttonOptions['icon'] : '';

            $data[] = [
                'name' => $definition->getName(),
                'label' => $this->translator->trans($definition->getLabel()),
                'title' => $this->translator->trans($title),
//                'hasDialog' => $operation->hasForm(),
//                'showDialog' => !empty($frontendOptions['show_dialog']),
                'icon' =>  $icon,
                'action' => $operation,
            ];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $this->context = $context;

        return $this;
    }
}
