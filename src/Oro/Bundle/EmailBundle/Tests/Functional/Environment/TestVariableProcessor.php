<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Environment;

use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class TestVariableProcessor implements VariableProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(string $variable, array $processorArguments, TemplateData $data): void
    {
        $value = null;
        if ('array' === $processorArguments['type']) {
            $object1 = new EmailTemplate();
            $object1->setSubject('testProperty2.object1 subject');

            $value = [
                'testProperty1' => 'testProperty1 value',
                'testProperty2' => [
                    'attribute1' => 'testProperty2.attribute1 value',
                    'attribute2' => [
                        'attribute21' => 'testProperty2.attribute2.attribute21 value'
                    ],
                    'object1' => $object1
                ]
            ];
        } elseif ('object' === $processorArguments['type']) {
            $value = new EmailTemplate();
            $value->setSubject('test subject');
        } elseif ('object_organization' === $processorArguments['type']) {
            $value = new Organization();
            $value->setName('EmailTemplate Organization');
            $value->setDescription('EmailTemplate Organization Description');
        }

        $data->setComputedVariable($variable, $value);
    }
}
