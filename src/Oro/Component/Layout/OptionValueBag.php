<?php

namespace Oro\Component\Layout;

/**
 * The container which allows to collect modifications of an block option value.
 */
class OptionValueBag
{
    /** @var Action[] */
    protected $actions = [];

    /**
     * Adds new value
     *
     * @param mixed $value
     */
    public function add($value)
    {
        $this->actions[] = new Action('add', [$value]);
    }

    /**
     * Removes value
     *
     * @param mixed $value
     */
    public function remove($value)
    {
        $this->actions[] = new Action('remove', [$value]);
    }

    /**
     * Removes value
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    public function replace($oldValue, $newValue)
    {
        $this->actions[] = new Action('replace', [$oldValue, $newValue]);
    }

    /**
     * Returns all actions
     *
     * @return Action[]
     */
    public function all()
    {
        return $this->actions;
    }

    /**
     * Builds a block option value using the given builder
     *
     * @param OptionValueBuilderInterface $builder
     *
     * @return mixed The built value
     */
    public function buildValue(OptionValueBuilderInterface $builder)
    {
        foreach ($this->actions as $action) {
            switch ($action->getName()) {
                case 'add':
                    $builder->add($action->getArgument(0));
                    break;
                case 'remove':
                    $builder->remove($action->getArgument(0));
                    break;
                case 'replace':
                    $builder->replace($action->getArgument(0), $action->getArgument(1));
                    break;
            }
        }

        return $builder->get();
    }
}
