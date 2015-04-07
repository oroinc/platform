<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;

class EmailAttachmentType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->em = $objectManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_email_attachment';
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => 'Oro\Bundle\EmailBundle\Entity\EmailAttachment',
            'intention'          => 'email_attachment',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'text', ['mapped' => false]);
        $builder->add('uploaded_file', 'file');

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'initAttachmentEntity']);
    }

    /**
     * @param FormEvent $event
     */
    public function initAttachmentEntity(FormEvent $event)
    {
        /** @var EmailAttachment $attachment */
        $attachment = $event->getData();

        if ($attachment instanceof EmailAttachment && $attachment->getUploadedFile()) {
            $attachmentContent = new EmailAttachmentContent();
            $attachmentContent->setContent(
                base64_encode(file_get_contents($attachment->getUploadedFile()->getRealPath()))
            );
            $attachmentContent->setContentTransferEncoding('base64');
            $attachmentContent->setEmailAttachment($attachment);

            $attachment->setContent($attachmentContent);
            $attachment->setContentType($attachment->getUploadedFile()->getMimeType());
            $attachment->setFileName($attachment->getUploadedFile()->getClientOriginalName());
        } elseif ($id = $event->getForm()->get('id')->getData()) {
            $repo = $this->em->getRepository('OroEmailBundle:EmailAttachment');
            $attachment = $repo->find($id);
            $attachment->setFile();
        }

        $event->setData($attachment);
    }
}
