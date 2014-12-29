<?php

namespace Oro\Bundle\UIBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Translation\TranslatorInterface;

class ActionButtonLabelProvider
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @var array
     *  key   = class name or empty string for default label
     *  value = [label: button label name, widget_title: widget title]
     * where:
     *  'label' is required
     *  'widget_title' is optional; if not specified the 'label' will be used as a widget title
     */
    protected $labels;

    /**
     * @param TranslatorInterface $translator
     * @param array               $labels
     */
    public function __construct(
        TranslatorInterface $translator,
        array $labels
    ) {
        $this->translator = $translator;
        $this->labels     = $labels;
    }

    /**
     * @param object $object
     *
     * @return string
     */
    public function getLabel($object)
    {
        if (!$object) {
            $label = $this->getValue('', 'label');
        } else {
            $className = ClassUtils::getClass($object);
            $label     = isset($this->labels[$className])
                ? $this->getValue($className, 'label')
                : $this->getValue('', 'label');
        }

        return $this->translator->trans($label);
    }

    /**
     * @param object $object
     *
     * @return string
     */
    public function getWidgetTitle($object)
    {
        if (!$object) {
            $label = $this->getValue('', 'widget_title', 'label');
        } else {
            $className = ClassUtils::getClass($object);
            $label     = isset($this->labels[$className])
                ? $this->getValue($className, 'widget_title', 'label')
                : $this->getValue('', 'widget_title', 'label');
        }

        return $this->translator->trans($label);
    }

    /**
     * @param string      $key
     * @param string      $attr
     * @param string|null $defaultAttr
     *
     * @return string
     */
    protected function getValue($key, $attr, $defaultAttr = null)
    {
        if ($defaultAttr) {
            return isset($this->labels[$key][$attr])
                ? $this->labels[$key][$attr]
                : $this->labels[$key][$defaultAttr];
        } else {
            return $this->labels[$key][$attr];
        }
    }
}
