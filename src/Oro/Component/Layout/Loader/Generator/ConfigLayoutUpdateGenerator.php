<?php

namespace Oro\Component\Layout\Loader\Generator;

use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\PhpUtils\ReflectionClassHelper;

class ConfigLayoutUpdateGenerator extends AbstractLayoutUpdateGenerator
{
    const NODE_ACTIONS = 'actions';

    const PATH_ATTR = '__path';

    /** @var ConfigLayoutUpdateGeneratorExtensionInterface[] */
    protected $extensions = [];

    /** @var ReflectionClassHelper */
    protected $helper;

    /**
     * @param ConfigLayoutUpdateGeneratorExtensionInterface $extension
     */
    public function addExtension(ConfigLayoutUpdateGeneratorExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGenerateBody(GeneratorData $data)
    {
        $body   = [];
        $source = $data->getSource();

        foreach ($source[self::NODE_ACTIONS] as $actionDefinition) {
            $actionName = key($actionDefinition);
            $arguments  = isset($actionDefinition[$actionName]) && is_array($actionDefinition[$actionName])
                ? $actionDefinition[$actionName] : [];

            $call = [];
            $this->normalizeActionName($actionName);
            $this->getHelper()->completeArguments($actionName, $arguments);

            array_walk(
                $arguments,
                function (&$arg) {
                    $arg = var_export($arg, true);
                }
            );
            $call[] = sprintf('$%s->%s(', self::PARAM_LAYOUT_MANIPULATOR, $actionName);
            $call[] = implode(', ', $arguments);
            $call[] = ');';

            $body[] = implode(' ', $call);
        }

        return implode("\n", $body);
    }

    /**
     * Validates given resource data, checks that "actions" node exists and consist valid actions.
     *
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function validate(GeneratorData $data)
    {
        $source = $data->getSource();

        if (!(is_array($source) && isset($source[self::NODE_ACTIONS]) && is_array($source[self::NODE_ACTIONS]))) {
            throw new SyntaxException(sprintf('expected array with "%s" node', self::NODE_ACTIONS), $source);
        }

        $actions = $source[self::NODE_ACTIONS];
        foreach ($actions as $nodeNo => $actionDefinition) {
            if (isset($actionDefinition[self::PATH_ATTR])) {
                $path = $actionDefinition[self::PATH_ATTR];
                unset($actionDefinition[self::PATH_ATTR]);
            } else {
                $path = self::NODE_ACTIONS . '.' . $nodeNo;
            }

            if (!is_array($actionDefinition)) {
                throw new SyntaxException('expected array with action name as key', $actionDefinition, $path);
            }

            $actionName = key($actionDefinition);
            $arguments  = is_array($actionDefinition[$actionName])
                ? $actionDefinition[$actionName] : [$actionDefinition[$actionName]];

            if (strpos($actionName, '@') !== 0) {
                throw new SyntaxException(
                    sprintf('action name should start with "@" symbol, current name "%s"', $actionName),
                    $actionDefinition,
                    $path
                );
            }

            $this->normalizeActionName($actionName);

            if (!$this->getHelper()->hasMethod($actionName)) {
                throw new SyntaxException(
                    sprintf('unknown action "%s", should be one of LayoutManipulatorInterface\'s methods', $actionName),
                    $actionDefinition,
                    $path
                );
            }

            if (!$this->getHelper()->isValidArguments($actionName, $arguments)) {
                throw new SyntaxException($this->getHelper()->getLastError(), $actionDefinition, $path);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepare(GeneratorData $data, VisitorCollection $visitorCollection)
    {
        foreach ($this->extensions as $extension) {
            $extension->prepare($data, $visitorCollection);
        }
    }

    /**
     * @return ReflectionClassHelper
     */
    protected function getHelper()
    {
        if (null === $this->helper) {
            $this->helper = new ReflectionClassHelper('Oro\Component\Layout\LayoutManipulatorInterface');
        }

        return $this->helper;
    }

    /**
     * Removes "@" sign from beginning of action name
     *
     * @param string $actionName
     */
    protected function normalizeActionName(&$actionName)
    {
        $actionName = substr($actionName, 1);
    }
}
