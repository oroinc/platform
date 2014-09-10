<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\ClassLoader;

/**
 * The goal of this class is to return false rather than raise an exception if a file cannot be loaded.
 * Actually this class has the same behaviour as Symfony's UniversalClassLoader.
 * Also see comment in ExtendClassLoadingUtils::registerClassLoader method for more details
 */
class ExtendClassLoader extends ClassLoader
{
    /**
     * {@inheritdoc}
     */
    public function loadClass($className)
    {
        if ($this->namespace !== null && strpos($className, $this->namespace.$this->namespaceSeparator) !== 0) {
            return false;
        }

        $file = ($this->includePath !== null ? $this->includePath . DIRECTORY_SEPARATOR : '')
            . str_replace($this->namespaceSeparator, DIRECTORY_SEPARATOR, $className)
            . $this->fileExtension;

        if (is_file($file)) {
            require $file;

            return true;
        }

        return false;
    }
}
