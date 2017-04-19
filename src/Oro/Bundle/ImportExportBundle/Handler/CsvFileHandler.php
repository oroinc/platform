<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Component\HttpFoundation\File\File as SymfonyComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CsvFileHandler
{
    const CRLF_LINE_ENDING = "\r\n";
    const LF_LINE_ENDING = "\n";
    const CR_LINE_ENDING = "\r";

    /**
     * Convert the ending-lines CR and LF in CRLF.
     *
     * @param SymfonyComponentFile $file
     */
    public function normalizeLineEndings(SymfonyComponentFile $file)
    {
        ini_set('auto_detect_line_endings', true);
        $handle = fopen($file->getRealPath(), 'r');
        $tempName = $file->getRealPath() . '_formatted';
        $formattedHandle = fopen($tempName, 'w');
        while (($line = fgets($handle)) !== false) {
            $formattedLine = $this->convertLineEndings($line);
            //Update the file contents
            fwrite($formattedHandle, $formattedLine);
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

    /**
     * @param $line
     * @return string
     */
    protected function convertLineEndings($line)
    {
        //Replace all the CRLF ending-lines by something uncommon
        $specialString = "!£#!Dont_wanna_replace_that!#£!";
        $line = str_replace(self::CRLF_LINE_ENDING, $specialString, $line);

        //Convert the CR ending-lines into CRLF ones
        $line = str_replace(self::CR_LINE_ENDING, self::CRLF_LINE_ENDING, $line);

        //Replace all the CRLF ending-lines by something uncommon
        $line = str_replace(self::CRLF_LINE_ENDING, $specialString, $line);

        //Convert the LF ending-lines into CRLF ones
        $line = str_replace(self::LF_LINE_ENDING, self::CRLF_LINE_ENDING, $line);

        //Restore the CRLF ending-lines
        $line = str_replace($specialString, self::CRLF_LINE_ENDING, $line);

        return $line;
    }
}
