<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\DataMapper\EmailTemplateDataMapperFactory;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
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
    public function __construct(
        private readonly ConfigManager $configManager,
        private readonly ConfigManager $userConfig,
        private readonly ManagerRegistry $doctrine,
        private readonly LocalizationManager $localizationManager,
        private readonly EmailTemplateDataMapperFactory $emailTemplateDataMapperFactory,
        private readonly HtmlTagHelper $htmlTagHelper
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $localizations = $this->localizationManager->getLocalizations();

        $builder
            ->add('entityName', HiddenType::class, [
                'attr' => [
                    'data-default-value' => Email::class,
                ],
                'constraints' => [
                    new Assert\Choice(['choices' => ['', Email::class]]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'oro.email.emailtemplate.type.label',
                'multiple' => false,
                'expanded' => true,
                'choices' => [
                    'oro.email.datagrid.emailtemplate.filter.type.html' => 'html',
                    'oro.email.datagrid.emailtemplate.filter.type.txt' => 'txt',
                ],
                'required' => true,
            ])
            ->add('translations', EmailTemplateTranslationCollectionType::class, [
                'localizations' => $localizations,
                'wysiwyg_enabled' => $this->userConfig->get('oro_email.email_template_wysiwyg_enabled') ?? false,
                'wysiwyg_options' => $this->getWysiwygOptions(),
                'block_name' => 'oro_email_emailtemplate',
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
                $signature = $this->htmlTagHelper->sanitize($this->configManager->get('oro_email.signature', ''));
                $emailTemplate->setContent($signature);
                $emailTemplate->setEntityName(Email::class);
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
                $proposedName .= random_int(0, 10000);
            }

            $template->setName($proposedName);
        });

        $builder->setDataMapper($this->emailTemplateDataMapperFactory->createDataMapper($builder->getDataMapper()));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplate::class,
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_autoresponse_template';
    }

    private function templateExists(?string $name): bool
    {
        if (!$name) {
            return false;
        }

        return (bool)$this->getEmailTemplateRepository()
            ->createQueryBuilder('et')
            ->select('COUNT(et.id)')
            ->where('et.name = :name AND et.entityName = :entityName')
            ->setParameter('name', $name)
            ->setParameter('entityName', Email::class)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getEmailTemplateRepository(): EmailTemplateRepository
    {
        return $this->doctrine->getRepository(EmailTemplate::class);
    }

    private function getWysiwygOptions(): array
    {
        if ($this->userConfig->get('oro_email.sanitize_html')) {
            return [];
        }

        return [
            'valid_elements' => null, //all elements are valid
            'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullscreen']),
            'relative_urls' => false,
            'entity_encoding' => 'raw',
        ];
    }
}
