<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as AttachmentModel;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailAttachmentType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var EmailAttachmentTransformer
     */
    protected $emailAttachmentTransformer;

    /**
     * @param ObjectManager              $objectManager
     * @param EmailAttachmentTransformer $emailAttachmentTransformer
     */
    public function __construct(ObjectManager $objectManager, EmailAttachmentTransformer $emailAttachmentTransformer)
    {
        $this->em = $objectManager;
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
            'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\EmailAttachment',
            'csrf_token_id'      => 'email_attachment',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', TextType::class);
        $builder->add('type', TextType::class, ['required' => true]);
        $builder->add('file', FileType::class);

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'initAttachmentEntity']);
    }

    /**
     * @param FormEvent $event
     *
     * @throws FormException
     */
    public function initAttachmentEntity(FormEvent $event)
    {
        /** @var AttachmentModel $attachment */
        $attachment = $event->getData();

        // this check is necessary due to inability to capture file input dialog cancel event
        if (!$attachment) {
            return;
        }

        if (!$attachment->getEmailAttachment()) {
            switch ($attachment->getType()) {
                case AttachmentModel::TYPE_ATTACHMENT:
                    $repo = $this->em->getRepository('OroAttachmentBundle:Attachment');
                    $oroAttachment = $repo->find($attachment->getId());
                    $emailAttachment = $this->emailAttachmentTransformer->oroToEntity($oroAttachment);

                    break;
                case AttachmentModel::TYPE_EMAIL_ATTACHMENT:
                    $repo = $this->em->getRepository('OroEmailBundle:EmailAttachment');
                    $emailAttachment = $repo->find($attachment->getId());

                    break;
                case AttachmentModel::TYPE_UPLOADED:
                    $emailAttachment = $this->emailAttachmentTransformer
                        ->entityFromUploadedFile($attachment->getFile());

                    break;
                default:
                    throw new FormException(sprintf('Invalid attachment type: %s', $attachment->getType()));
            }

            $attachment->setEmailAttachment($emailAttachment);
        }

        $event->setData($attachment);
    }
}
