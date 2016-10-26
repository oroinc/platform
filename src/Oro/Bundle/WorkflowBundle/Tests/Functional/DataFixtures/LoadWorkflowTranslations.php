<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class LoadWorkflowTranslations extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const WORKFLOW1 = 'workflow1';
    const WORKFLOW2 = 'workflow2';
    const WORKFLOW3 = 'workflow3';

    const TRANSLATION1 = 'translation1.key';
    const TRANSLATION2 = 'translation2.key';

    /** @var array */
    protected $workflowTranslations = [
        Translation::DEFAULT_LOCALE => [
            self::WORKFLOW1 => [
                self::TRANSLATION1 => 'translation1-1.default',
            ],
        ],
        LoadLanguages::LANGUAGE2 => [
            self::WORKFLOW1 => [
                self::TRANSLATION1 => 'translation1-1.value',
                self::TRANSLATION2 => 'translation1-2.value',
            ],
            self::WORKFLOW2 => [
                self::TRANSLATION1 => 'translation2-1.value',
            ],
            self::WORKFLOW3 => [],
        ],
    ];

    /** @var array */
    protected $translations = [
        Translation::DEFAULT_LOCALE => [
            self::TRANSLATION1 => 'translation1.default',
        ],
        LoadLanguages::LANGUAGE2 => [
            self::TRANSLATION1 => 'translation1.value',
        ],
    ];

    /** @var TranslationManager */
    protected $translationManager;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadTranslations::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $generator = new TranslationKeyGenerator();

        foreach ($this->workflowTranslations as $locale => $workflows) {
            foreach ($workflows as $workflowName => $translations) {
                $keyPrefix = $generator->generate(
                    new TranslationKeySource(new WorkflowTemplate(), ['workflow_name' => $workflowName])
                );

                foreach ($translations as $key => $translation) {
                    $this->createTranslation(sprintf('%s.%s', $keyPrefix, $key), $translation, $locale);
                }
            }
        }

        foreach ($this->translations as $locale => $translations) {
            foreach ($translations as $key => $value) {
                $this->createTranslation($key, $value, $locale);
            }
        }

        $this->getTranslationManager()->flush();
    }

    protected function createTranslation($key, $value, $locale)
    {
        $translationManager = $this->getTranslationManager();

        return $translationManager->saveValue($key, $value, $locale, WorkflowTranslationHelper::TRANSLATION_DOMAIN);
    }

    /**
     * @return TranslationManager
     */
    protected function getTranslationManager()
    {
        if (!$this->translationManager) {
            $this->translationManager = $this->container->get('oro_translation.manager.translation');
        }

        return $this->translationManager;
    }
}
