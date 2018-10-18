<?php

namespace Oro\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting\ClassCommentSniff as BaseClassCommentSniff;

/**
 * This sniff checks if class/interface/trait documentation exists ind it have text doc that describes
 * what this class for.
 *
 * @category PHP
 */
class ClassCommentSniff extends BaseClassCommentSniff
{
    /**
     * Tags in correct order and related info.
     * This array cannot be configured from the outside.
     *
     * @var array
     */
    protected $tags = [
        '@category'   => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@package'    => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@subpackage' => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@author'     => [
            'required'       => false,
            'allow_multiple' => true,
        ],
        '@copyright'  => [
            'required'       => false,
            'allow_multiple' => true,
        ],
        '@license'    => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@version'    => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@link'       => [
            'required'       => false,
            'allow_multiple' => true,
        ],
        '@see'        => [
            'required'       => false,
            'allow_multiple' => true,
        ],
        '@since'      => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@deprecated' => [
            'required'       => false,
            'allow_multiple' => false,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        parent::process($phpcsFile, $stackPtr);

        $tokens = $phpcsFile->getTokens();
        $objectType = trim($tokens[$stackPtr]['content']);
        $objectNamePosition = $phpcsFile->findNext(T_STRING, $stackPtr);
        $objectName = trim($tokens[$objectNamePosition]['content']);

        // find the previous comment block
        $objectDoctypeOpenPosition = $phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPtr);

        if (!$objectDoctypeOpenPosition) {
            return;
        }

        $objectDoctypeContentPosition = $phpcsFile->findNext(
            T_DOC_COMMENT_STRING,
            $objectDoctypeOpenPosition,
            $tokens[$objectDoctypeOpenPosition]['comment_closer']
        );

        // check if class have no documentation
        // 5 is the position where description should start.
        if (!$objectDoctypeContentPosition || $tokens[$objectDoctypeContentPosition]['column'] > 5) {
            $error = '%s should contain text documentation what is it for';
            $phpcsFile->addError($error, $stackPtr, 'Class doc', [ucfirst($objectType)]);
            return;
        }

        $docText = $tokens[$objectDoctypeContentPosition]['content'];

        // check format "objectName"
        if (strtolower($docText) === strtolower($objectName)) {
            $error = 'The descripion "%s" is not valid because contains only the %s name';
            $phpcsFile->addError($error, $stackPtr, 'Class doc', [$docText, $objectType]);
        }

        // check format "objectType objectName"
        if (strtolower($objectType . $objectName) === str_replace(' ', '', strtolower($docText))) {
            $error = 'The descripion "%s" is not valid because contains only the object type and name';
            $phpcsFile->addError($error, $stackPtr, 'Class doc', [$docText]);
        }
    }
}
