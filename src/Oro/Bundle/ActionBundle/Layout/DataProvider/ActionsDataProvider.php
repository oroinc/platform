<?php

namespace Oro\Bundle\ActionBundle\Layout\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionManager;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

class ActionsDataProvider implements DataProviderInterface
{
    /**
     * @var ActionManager
     */
    protected $actionManager;

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
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     * @param RestrictHelper $restrictHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ActionManager $actionManager,
        ContextHelper $contextHelper,
        RestrictHelper $restrictHelper,
        TranslatorInterface $translator
    ) {
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
        $this->restrictHelper = $restrictHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'oro_action';
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
        if (!$this->context->data()->has('entity')) {
            return [];
        }

        $context = $this->contextHelper->getActionParameters(['entity' => $this->context->data()->get('entity')]);
        $actions = $this->restrictHelper->restrictActionsByGroup($this->actionManager->getActions($context), $groups);

        return $this->getPreparedData($actions);
    }

    /**
     * @param Action[] $actions
     * @return array
     */
    protected function getPreparedData(array $actions = [])
    {
        $data = [];
        foreach ($actions as $action) {
            if (!$action->getDefinition()->isEnabled()) {
                continue;
            }

            $definition = $action->getDefinition();

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
                'icon' =>  $icon,
                'action' => $action,
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
