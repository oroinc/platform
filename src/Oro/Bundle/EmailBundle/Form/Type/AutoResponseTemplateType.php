<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType;
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

    /**
     * @param ConfigManager $cm
     * @param ConfigManager $userConfig
     * @param LocaleSettings $localeSettings
     * @param Registry $registry
     */
    public function __construct(
        ConfigManager $cm,
        ConfigManager $userConfig,
        LocaleSettings $localeSettings,
        Registry $registry
    ) {
        $this->cm             = $cm;
        $this->userConfig     = $userConfig;
        $this->localeSettings = $localeSettings;
        $this->registry       = $registry;
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
        $languages = $this->userConfig->get('oro_locale.languages');

        return array_unique(array_merge($languages, [$this->localeSettings->getLanguage()]));
    }

    /**
     * @return array
     */
    protected function getLocaleLabels()
    {
        return $this->localeSettings->getLocalesByCodes($this->getLanguages(), $this->localeSettings->getLanguage());
    }
}
