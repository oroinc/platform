<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Generator\Writer;

class ClassBuilder
{
    /** @var Writer */
    protected $writer;

    public function __construct()
    {
        $this->writer = new Writer();
    }

    /**
     * @param string $methodName
     * @param string $methodBody
     * @param array  $methodArgs
     *
     * @return PhpMethod
     */
    public function generateClassMethod($methodName, $methodBody, $methodArgs = [])
    {
        $this->writer->reset();

        $method = PhpMethod::create($methodName)->setBody(
            $this->writer->write($methodBody)->getContent()
        );

        if (count($methodArgs)) {
            foreach ($methodArgs as $arg) {
                $method->addParameter(PhpParameter::create($arg));
            }
        }

        return $method;
    }
}
