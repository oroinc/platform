<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\Generator;

use CG\Generator\Writer;
use CG\Generator\PhpClass;
use CG\Generator\DefaultVisitor;

class Visitor extends DefaultVisitor
{
    /** @var \ReflectionProperty */
    protected $refField;

    /**
     * TODO remove this fix backport CG lib version > 1.0.0
     *
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function startVisitingClass(PhpClass $class)
    {
        if ($namespace = $class->getNamespace()) {
            $this->getWriter()->write('namespace ' . $namespace . ';' . "\n\n");
        }

        if ($files = $class->getRequiredFiles()) {
            foreach ($files as $file) {
                $this->getWriter()->writeln('require_once ' . var_export($file, true) . ';');
            }

            $this->getWriter()->write("\n");
        }

        if ($useStatements = $class->getUseStatements()) {
            foreach ($useStatements as $alias => $namespace) {
                $this->getWriter()->write('use ' . $namespace);

                if (substr($namespace, strrpos($namespace, '\\') + 1) !== $alias) {
                    $this->getWriter()->write(' as ' . $alias);
                }

                $this->getWriter()->write(";\n");
            }

            $this->getWriter()->write("\n");
        }

        if ($docblock = $class->getDocblock()) {
            $this->getWriter()->write($docblock);
        }
        if ($class->isAbstract()) {
            $this->getWriter()->write('abstract ');
        }
        if ($class->isFinal()) {
            $this->getWriter()->write('final ');
        }

        $this->getWriter()->write('class ' . $class->getShortName());

        if ($parentClassName = $class->getParentClassName()) {
            $this->getWriter()->write(' extends ' . ('\\' . ltrim($parentClassName, '\\')));
        }

        $interfaceNames = $class->getInterfaceNames();
        if (!empty($interfaceNames)) {
            $interfaceNames = array_unique($interfaceNames);

            $interfaceNames = array_map(
                function ($name) {
                    if ('\\' === $name[0]) {
                        return $name;
                    }

                    return '\\' . $name;
                },
                $interfaceNames
            );

            $this->getWriter()->write(' implements ' . implode(', ', $interfaceNames));
        }

        $this->getWriter()
            ->write("\n{\n")
            ->indent();
    }

    /**
     * @return Writer
     */
    protected function getWriter()
    {
        if (null == $this->refField) {
            $this->refField = new \ReflectionProperty(get_parent_class($this), 'writer');
            $this->refField->setAccessible(true);
        }

        return $this->refField->getValue($this);
    }
}
