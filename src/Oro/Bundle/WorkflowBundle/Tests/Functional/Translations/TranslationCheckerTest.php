<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Translations;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowDefinitionTranslationFieldsIterator;
use Symfony\Component\Translation\TranslatorInterface;

class TranslationCheckerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testWorkflowTranslations()
    {
        /** @var WorkflowDefinition[]|array $definitions */
        $definitions = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(WorkflowDefinition::class)
            ->getRepository(WorkflowDefinition::class)
            ->findAll();
        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        $notTranslatedKeys = [];

        foreach ($definitions as $definition) {
            $workflowDefinitionFieldsIterator = new WorkflowDefinitionTranslationFieldsIterator($definition);
            foreach ($workflowDefinitionFieldsIterator as $key => $keyValue) {
                if ($this->isNotRequiredField($key)) {
                    continue;
                }
                $translated = $translator->trans(
                    $key,
                    [],
                    WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                    Translator::DEFAULT_LOCALE
                );
                if ($translated === $key) {
                    $notTranslatedKeys[] = $key;
                }
            }
        }

        $this->assertEquals([], $notTranslatedKeys, 'Some workflow keys are not translated');
    }

    /**
     * @param string $field
     * @return int
     */
    protected function isNotRequiredField($field)
    {
        return preg_match(
            '/^oro\.workflow\..+\.transition\..+\.(warning_message|button_label|button_title|attribute\..+)$/',
            $field
        );
    }
}
