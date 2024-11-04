<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Common;

use Oro\Bundle\ApiBundle\Tests\Functional\ValidateClassExistenceApplicableChecker;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ProcessorBag;

class ClassesInProcessorTagsTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getProcessorBag(): ProcessorBag
    {
        return self::getContainer()->get('oro_api.tests.processor_bag');
    }

    public function testClassesFromProcessorTagsExist(): void
    {
        $errors = [];
        $processorBag = $this->getProcessorBag();
        foreach ($processorBag->getActions() as $action) {
            $context = new Context();
            $context->setAction($action);
            $processors = $processorBag->getProcessors($context);
            $processors->setApplicableChecker(new ValidateClassExistenceApplicableChecker(
                self::getContainer()->get('oro_entity_extend.entity_class_provider.enum_option')
            ));
            try {
                foreach ($processors as $processor) {
                    // do nothing; existence of classes is validated by the applicable checker
                }
            } catch (\InvalidArgumentException $e) {
                $errors[$processors->getProcessorId()] = $e->getMessage();
            }
        }
        if ($errors) {
            $errorMessage = '';
            foreach ($errors as $processorId => $msg) {
                $errorMessage .= "\n" . sprintf('  %s Processor: %s.', $msg, $processorId);
            }
            throw new \InvalidArgumentException($errorMessage);
        }
    }
}
