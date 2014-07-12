<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Generator\Writer;

abstract class AbstractEntityGeneratorExtension
{
    /**
     * Check if this extension should be involved in PHP code generation
     *
     * @param array $schema The entity schema
     *
     * @return bool
     */
    abstract public function supports(array $schema);

    /**
     * This method is called during the PHP code generation.
     * You can use it to make modifications of entity PHP code.
     *
     * @param array    $schema The entity schema
     * @param PhpClass $class  The php class builder
     *
     * @return void
     */
    abstract public function generate(array $schema, PhpClass $class);

    /**
     * @param string $methodName
     * @param string $methodBody
     * @param array  $methodArgs
     *
     * @return PhpMethod
     */
    protected function generateClassMethod($methodName, $methodBody, $methodArgs = [])
    {
        $writer = new Writer();

        $method = PhpMethod::create($methodName)->setBody(
            $writer->write($methodBody)->getContent()
        );

        if (count($methodArgs)) {
            foreach ($methodArgs as $arg) {
                $method->addParameter(PhpParameter::create($arg));
            }
        }

        return $method;
    }
}
