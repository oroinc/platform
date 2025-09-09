<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromSystemConfig;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Form type for Email Attachments.
 */
class EmailAttachmentType extends AbstractType implements ResetInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EmailAttachmentTransformer */
    private $emailAttachmentTransformer;

    /**
     * Cache for already loaded EmailAttachment entities to avoid multiple queries for the same entity
     * during one form submission.
     *
     * @var array<int,array<EmailAttachment>> EmailAttachment entities grouped by EmailTemplateAttachment id
     */
    private array $emailAttachmentEntitiesCache = [];

    public function __construct(ManagerRegistry $doctrine, EmailAttachmentTransformer $emailAttachmentTransformer)
    {
        $this->doctrine = $doctrine;
        $this->emailAttachmentTransformer = $emailAttachmentTransformer;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_attachment';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailAttachmentModel::class,
            'csrf_token_id' => 'email_attachment',
        ]);
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', TextType::class);
        $builder->add('type', TextType::class, ['required' => true]);
        $builder->add(
            'file',
            FileType::class,
            [
                'constraints' => [
                    new FileConstraintFromSystemConfig([
                        'maxSizeConfigPath' => 'oro_email.attachment_max_size',
                    ]),
                ],
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->reset(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->initAttachmentEntity(...));
    }

    /**
     * @throws FormException
     */
    public function initAttachmentEntity(FormEvent $event)
    {
        /** @var EmailAttachmentModel $emailAttachmentModel */
        $emailAttachmentModel = $event->getData();

        // this check is necessary due to inability to capture file input dialog cancel event
        if (!$emailAttachmentModel) {
            return;
        }

        $emailAttachment = null;
        if (!$emailAttachmentModel->getEmailAttachment()) {
            switch ($emailAttachmentModel->getType()) {
                case EmailAttachmentModel::TYPE_ATTACHMENT:
                    $emailAttachment = $this->emailAttachmentTransformer->attachmentEntityToEntity(
                        $this->doctrine->getRepository(Attachment::class)->find($emailAttachmentModel->getId())
                    );

                    break;
                case EmailAttachmentModel::TYPE_EMAIL_ATTACHMENT:
                    $emailAttachment = $this->doctrine->getRepository(EmailAttachment::class)
                        ->find($emailAttachmentModel->getId());

                    break;
                case EmailAttachmentModel::TYPE_UPLOADED:
                    if ($emailAttachmentModel->getFile() instanceof UploadedFile) {
                        $emailAttachment = $this->emailAttachmentTransformer
                            ->entityFromUploadedFile($emailAttachmentModel->getFile());
                    }

                    break;
                case EmailAttachmentModel::TYPE_EMAIL_TEMPLATE_ATTACHMENT:
                    $emailAttachment = $this->initAttachmentEntityFromEmailTemplateAttachment(
                        $emailAttachmentModel,
                        $event
                    );

                    break;
                default:
                    $event->getForm()->addError(
                        new FormError(sprintf('Invalid attachment type: %s', (int)$emailAttachmentModel->getType()))
                    );
            }

            $emailAttachmentModel->setEmailAttachment($emailAttachment);
        }

        $event->setData($emailAttachmentModel);
    }

    private function initAttachmentEntityFromEmailTemplateAttachment(
        EmailAttachmentModel $emailAttachmentModel,
        FormEvent $event
    ): ?EmailAttachment {
        [$emailTemplateAttachmentId, $index] = explode(':', $emailAttachmentModel->getId()) + [0, 0];
        if (!isset($this->emailAttachmentEntitiesCache[$emailTemplateAttachmentId])) {
            $emailTemplateAttachment = $this->doctrine->getRepository(EmailTemplateAttachment::class)
                ->find($emailTemplateAttachmentId);
            if ($emailTemplateAttachment !== null) {
                /** @var EmailModel|null $emailModel */
                $emailModel = $event->getForm()->getParent()?->getParent()?->getData();
                if ($emailModel?->getEntityClass() && $emailModel?->getEntityId()) {
                    $rootEntity = $this->doctrine->getRepository($emailModel->getEntityClass())
                        ->find($emailModel->getEntityId());
                }

                $this->emailAttachmentEntitiesCache[$emailTemplateAttachmentId] = $this->emailAttachmentTransformer
                    ->entityFromEmailTemplateAttachment(
                        $emailTemplateAttachment,
                        ['entity' => $rootEntity ?? null]
                    );
            }
        }

        return $this->emailAttachmentEntitiesCache[$emailTemplateAttachmentId][$index] ?? null;
    }

    #[\Override]
    public function reset(): void
    {
        $this->emailAttachmentEntitiesCache = [];
    }
}
