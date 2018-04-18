<?php

namespace Oro\Bundle\TranslationBundle\Form\TranslationForm;

use Doctrine\Common\Util\ClassUtils;
use Gedmo\Translatable\TranslatableListener;

/**
 * Handles logic for getting translatable fields and config for class
 */
class GedmoTranslationForm extends AbstractTranslationForm
{
    /**
     * @var TranslatableListener
     */
    private $gedmoTranslatableListener;

    /**
     * @var array
     */
    private $gedmoConfig;

    /**
     * @return TranslatableListener
     */
    public function getGedmoTranslatableListener()
    {
        return $this->gedmoTranslatableListener;
    }

    /**
     * @param TranslatableListener $gedmoTranslatableListener
     */
    public function setGedmoTranslatableListener(TranslatableListener $gedmoTranslatableListener)
    {
        $this->gedmoTranslatableListener = $gedmoTranslatableListener;
    }

    /**
     * @param string $translatableClass
     * @return string
     */
    private function getGedmoConfig($translatableClass)
    {
        if (isset($this->gedmoConfig[$translatableClass])) {
            return $this->gedmoConfig[$translatableClass];
        }

        $translatableClass = ClassUtils::getRealClass($translatableClass);
        $manager = $this->getManagerRegistry()->getManagerForClass($translatableClass);
        $this->gedmoConfig[$translatableClass] =
            $this->gedmoTranslatableListener->getConfiguration($manager, $translatableClass);

        return $this->gedmoConfig[$translatableClass];
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationClass($translatableClass)
    {
        $gedmoConfig = $this->getGedmoConfig($translatableClass);

        return $gedmoConfig['translationClass'];
    }

    /**
     * @param string $translatableClass
     * @return array
     */
    protected function getTranslatableFields($translatableClass)
    {
        $gedmoConfig = $this->getGedmoConfig($translatableClass);

        return isset($gedmoConfig['fields']) ? $gedmoConfig['fields'] : [];
    }
}
