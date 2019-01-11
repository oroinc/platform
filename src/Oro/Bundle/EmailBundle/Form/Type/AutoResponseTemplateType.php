<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Used to create rule for mailbox in system Configuration.
 */
class AutoResponseTemplateType extends AbstractType
{
    /** @var ConfigManager */
    protected $cm;

    /** @var ConfigManager */
    protected $userConfig;

    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var Registry */
    protected $registry;

    /** @var LocalizationManager */
    protected $localizationManager;

    /**
     * @param ConfigManager $cm
     * @param ConfigManager $userConfig
     * @param LocaleSettings $localeSettings
     * @param Registry $registry
     * @param LocalizationManager $localizationManager
     */
    public function __construct(
        ConfigManager $cm,
        ConfigManager $userConfig,
        LocaleSettings $localeSettings,
        Registry $registry,
        LocalizationManager $localizationManager
    ) {
        $this->cm = $cm;
        $this->userConfig = $userConfig;
        $this->localeSettings = $localeSettings;
        $this->registry = $registry;
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('entityName', HiddenType::class, [
                'attr' => [
                    'data-default-value' => Email::ENTITY_CLASS,
                ],
                'constraints' => [
                    new Assert\Choice([
                        'choices' => [
                            '',
                            Email::ENTITY_CLASS,
                        ],
                    ]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label'    => 'oro.email.emailtemplate.type.label',
                'multiple' => false,
                'expanded' => true,
                'choices'  => [
                    'oro.email.datagrid.emailtemplate.filter.type.html' => 'html',
                    'oro.email.datagrid.emailtemplate.filter.type.txt' => 'txt',
                ],
                'required' => true
            ])
            ->add('translations', EmailTemplateTranslationType::class, [
                'label'    => 'oro.email.emailtemplate.translations.label',
                'locales'  => $this->getLanguages(),
                'labels'   => $this->getLocaleLabels(),
                'content_options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'attr' => [
                        'data-default-value' => $this->cm->get('oro_email.signature', ''),
                    ],
                ],
                'subject_options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ],
            ])
            ->add('visible', CheckboxType::class, [
                'label' => 'oro.email.autoresponserule.form.template.visible.label',
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            if ($form->has('owner')) {
                $form->remove('owner');
            }

            if (!$event->getData()) {
                $emailTemplate = new EmailTemplate();
                $emailTemplate->setContent($this->cm->get('oro_email.signature', ''));
                $emailTemplate->setEntityName(Email::ENTITY_CLASS);
                $event->setData($emailTemplate);
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $template = $event->getData();
            if (!$template || $template->getName()) {
                return;
            }

            $proposedName = $template->getSubject();
            while ($this->templateExists($proposedName)) {
                $proposedName .= rand(0, 10);
            }

            $template->setName($proposedName);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\EmailBundle\Entity\EmailTemplate',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_email_autoresponse_template';
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function templateExists($name)
    {
        return (bool) $this->getEmailTemplateRepository()
            ->createQueryBuilder('et')
            ->select('COUNT(et.id)')
            ->where('et.name = :name')
            ->andWhere('et.entityName = :entityName')
            ->setParameters([
                'name' => $name,
                'entityName' => Email::ENTITY_CLASS,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return EmailTemplateRepository
     */
    protected function getEmailTemplateRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:EmailTemplate');
    }

    /**
     * @return array
     */
    protected function getLanguages()
    {
        return array_map(function (Localization $localization) {
            return $localization->getLanguageCode();
        }, $this->getEnabledLocalizations());
    }

    /**
     * @return Localization[]
     */
    private function getEnabledLocalizations()
    {
        $ids = array_map(function ($id) {
            return (int)$id;
        }, (array)$this->userConfig->get(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS)));

        return $this->localizationManager->getLocalizations($ids);
    }

    /**
     * @return array
     */
    protected function getLocaleLabels()
    {
        return $this->localeSettings->getLocalesByCodes($this->getLanguages(), $this->localeSettings->getLanguage());
    }
}
