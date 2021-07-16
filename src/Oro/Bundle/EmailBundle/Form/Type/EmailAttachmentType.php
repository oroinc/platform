<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromSystemConfig;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Email Attachments.
 */
class EmailAttachmentType extends AbstractType
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EmailAttachmentTransformer */
    private $emailAttachmentTransformer;

    public function __construct(ManagerRegistry $doctrine, EmailAttachmentTransformer $emailAttachmentTransformer)
    {
        $this->doctrine = $doctrine;
        $this->emailAttachmentTransformer = $emailAttachmentTransformer;
    }

    /**
     * {@inheritDoc}
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
        return 'oro_email_attachment';
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailAttachmentModel::class,
            'csrf_token_id' => 'email_attachment',
        ]);
    }

    /**
     * {@inheritDoc}
     */
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
                        'maxSizeConfigPath' => 'oro_email.attachment_max_size'
                    ])
                ]
            ]
        );

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'initAttachmentEntity']);
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
                default:
                    throw new FormException(sprintf('Invalid attachment type: %s', $emailAttachmentModel->getType()));
            }

            $emailAttachmentModel->setEmailAttachment($emailAttachment);
        }

        $event->setData($emailAttachmentModel);
    }
}
