<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Component\HttpFoundation\File\File as SymfonyComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CsvFileHandler
{
    /**
     * Convert the ending-lines CR and LF in CRLF.
     *
     * @param string $filename Name of the file
     */
    public function normalizeLineEndings(SymfonyComponentFile $file)
    {
        ini_set('auto_detect_line_endings', true);
        $handle = fopen($file->getRealPath(), 'r');
        $tempName = $file->getRealPath() . '_formatted';
        $formattedHandle = fopen($tempName, 'w');
        while(($line = fgets($handle)) !== false) {
            //Replace all the CRLF ending-lines by something uncommon
            $dontReplaceThisString = "\r\n";
            $specialString = "!£#!Dont_wanna_replace_that!#£!";
            $line = str_replace($dontReplaceThisString, $specialString, $line);

            //Convert the CR ending-lines into CRLF ones
            $line = str_replace("\r", "\r\n", $line);

            //Replace all the CRLF ending-lines by something uncommon
            $line = str_replace($dontReplaceThisString, $specialString, $line);

            //Convert the LF ending-lines into CRLF ones
            $line = str_replace("\n", "\r\n", $line);

            //Restore the CRLF ending-lines
            $line = str_replace($specialString, $dontReplaceThisString, $line);

            //Update the file contents
            fwrite($formattedHandle, $line);
        }
        $formattedFile = new UploadedFile(
            $tempName,
            $file->getClientOriginalName(),
            'text/csv',
            filesize($tempName)
        );
        fclose($handle);
        fclose($formattedHandle);

        return $formattedFile;
    }
}
