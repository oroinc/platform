<?php

namespace Oro\Bundle\ImapBundle\Mail\Processor;

use Zend\Mail\Storage\Part\PartInterface;

class ImageExtractor implements ContentIdExtractorInterface
{
    /**
     * @param PartInterface $multipart
     *
     * @return array
     */
    public function extract(PartInterface $multipart)
    {
        $contents = [];
        /** @var PartInterface $part */
        foreach ($multipart as $part) {
            $headers = $part->getHeaders();
            if ($this->supports($part)) {
                $cid                        = $headers->get('Content-ID')->getFieldValue();
                $stringToReplace            = 'cid:' . substr($cid, 1, strlen($cid) - 2);
                $replacement                = sprintf(
                    'data:%s;base64,%s',
                    $headers->get('Content-Type')->getType(),
                    $part->getContent()
                );
                $contents[$stringToReplace] = $replacement;
            }
        }

        return $contents;
    }

    /**
     * @param PartInterface $part
     *
     * @return bool
     */
    protected function supports(PartInterface $part)
    {
        return $this->hasContentId($part) && $this->isImage($part) && $this->isBase64Encoded($part);
    }

    /**
     * @param PartInterface $part
     *
     * @return bool
     */
    protected function hasContentId(PartInterface $part)
    {
        return $part->getHeaders()->has('Content-ID');
    }

    /**
     * @param PartInterface $part
     *
     * @return bool
     */
    protected function isImage(PartInterface $part)
    {
        return $part->getHeaders()->has('Content-Type')
            && strpos($part->getHeaders()->get('Content-Type')->getFieldValue(), 'image/') === 0;
    }

    /**
     * @param PartInterface $part
     *
     * @return bool
     */
    protected function isBase64Encoded(PartInterface $part)
    {
        return $part->getHeaders()->has('Content-Transfer-Encoding')
            && $part->getHeaders()->get('Content-Transfer-Encoding')->getFieldValue() === 'base64';
    }
}
