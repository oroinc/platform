<?php

namespace Oro\Bundle\UIBundle\Twig;

class RegexExtension extends \Twig_Extension
{
    const NAME = 'oro_regex';

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_preg_replace', [$this, 'pregReplace'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param string $pattern
     * @param string $replacement
     * @param string $subject
     *
     * @return mixed
     */
    public function pregReplace($pattern, $replacement = '', $subject = '')
    {
        if (is_string($subject) && strlen($subject)) {
            $subject = preg_replace($pattern, $replacement, $subject);
        }

        return $subject;
    }
}
