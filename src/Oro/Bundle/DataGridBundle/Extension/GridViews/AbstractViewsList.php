<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;

abstract class AbstractViewsList
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var null|ArrayCollection */
    protected $views = null;
    
    /** @var array */
    protected $systemViews = [];

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Returns an array of available views
     *
     * @return View[]
     */
    abstract protected function getViewsList();

    /**
     * Return all system views that defined in ViewList
     * @return array
     */
    public function getSystemViewsList()
    {
        $views = [];
        foreach ($this->systemViews as $view) {
            $systemView = new View(
                $view['name'],
                $view['filters'],
                $view['sorters'],
                'system',
                $view['columns']
            );
            if ($view['is_default']) {
                $systemView->setDefault(true);
            }
            $systemView->setLabel($this->translator->trans($view['label']));
            $views[] = $systemView;
        }

        return $views;
    }

    /**
     * Public interface to retrieve list
     *
     * @return ArrayCollection
     */
    public function getList()
    {
        if (!$this->views instanceof ArrayCollection) {
            $list = $this->getViewsList();
            $this->validate($list);

            $this->views = new ArrayCollection($list);
        }

        return $this->views;
    }

    /**
     * Find and returns view object by name
     *
     * @param string $name
     *
     * @return View|bool
     */
    public function getViewByName($name)
    {
        if (empty($name)) {
            return false;
        }

        $filtered = $this->getList()->filter(
            function (View $view) use ($name) {
                return $view->getName() === $name;
            }
        );

        return $filtered->first();
    }

    /**
     * Returns array of choices for choice widget
     *
     * @return array
     */
    public function toChoiceList()
    {
        $choices = [];

        /** @var View[] $views */
        $views = $this->getList();
        foreach ($views as $view) {
            $choices[] = ['value' => $view->getName(), 'label' => $this->translator->trans($view->getLabel())];
        }

        return $choices;
    }

    /**
     * Returns metadata array
     *
     * @return array
     */
    public function getMetadata()
    {
        $result = $this->getList()->map(
            function (View $view) {
                return $view->getMetadata();
            }
        );

        return $result->toArray();
    }

    /**
     * Validates input array
     *
     * @param array $list
     *
     * @throws InvalidArgumentException
     */
    protected function validate(array $list)
    {
        foreach ($list as $view) {
            if (!$view instanceof View) {
                throw new InvalidArgumentException('List should contain only instances of View class');
            }
        }
    }
}
