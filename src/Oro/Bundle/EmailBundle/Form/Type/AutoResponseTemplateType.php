<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\DataMapper\LocalizationAwareEmailTemplateDataMapper;
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
    /** @var ConfigManager */
    protected $cm;

    /** @var ConfigManager */
    protected $userConfig;

    /** @var Registry */
    protected $registry;

    /** @var LocalizationManager */
    protected $localizationManager;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    public function __construct(
        ConfigManager $cm,
        ConfigManager $userConfig,
        Registry $registry,
        LocalizationManager $localizationManager,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->cm = $cm;
        $this->userConfig = $userConfig;
        $this->registry = $registry;
        $this->localizationManager = $localizationManager;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $localizations = $this->localizationManager->getLocalizations();

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
                'wysiwyg_enabled' => $this->userConfig->get('oro_form.wysiwyg_enabled') ?? false,
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
                $signature = $this->htmlTagHelper->sanitize($this->cm->get('oro_email.signature', ''));
                $emailTemplate->setContent($signature);
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
                $proposedName .= random_int(0, 10000);
            }

            $template->setName($proposedName);
        });

        $builder->setDataMapper(new LocalizationAwareEmailTemplateDataMapper($builder->getDataMapper()));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplate::class,
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
        return (bool)$this->getEmailTemplateRepository()
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
        return $this->registry->getRepository(EmailTemplate::class);
    }

    /**
     * @return array
     */
    protected function getWysiwygOptions()
    {
        if ($this->userConfig->get('oro_email.sanitize_html')) {
            return [];
        }

        return [
            'valid_elements' => null, //all elements are valid
            'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullpage']),
            'relative_urls' => true,
            'forced_root_block' => '',
            'entity_encoding' => 'raw',
        ];
    }
}
