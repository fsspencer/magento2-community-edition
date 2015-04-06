<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Update;

/**
 * Class which provides access to the current status of the Magento updater application.
 *
 * Each job is using this class to share information about its current status.
 * Current status can be seen on the updater app web page.
 */
class Status
{
    /**
     * @var string
     */
    protected $statusFilePath;

    /**
     * Initialize.
     *
     * @param string|null $statusFilePath
     */
    public function __construct($statusFilePath = null)
    {
        $this->statusFilePath = $statusFilePath ? $statusFilePath : UPDATER_BP . '/var/.update_status.txt';
    }

    /**
     * Get current updater application status.
     *
     * The last N status lines only may be requested using $maxNumberOfLines argument.
     *
     * To avoid display area overflow due to having overly long lines, $lineLengthLimit can be used.
     * E.g. if some line is 2.3 times longer than $lineLengthLimit, it will account for 3 lines.
     *
     * @param int|null $maxNumberOfLines
     * @param int $lineLengthLimit
     * @return string
     */
    public function get($maxNumberOfLines = null, $lineLengthLimit = 120)
    {
        $status = '';
        if (file_exists($this->statusFilePath)) {
            $fullStatusArray = file($this->statusFilePath);
            $linesInFile = count($fullStatusArray);
            if (!$maxNumberOfLines || ($maxNumberOfLines > $linesInFile)) {
                $maxNumberOfLines = $linesInFile;
            }
            $totalNumberOfLinesOnDisplay = 0;
            $totalLinesToRead = $maxNumberOfLines;
            for ($currentLineNumber = 1; $currentLineNumber <= $maxNumberOfLines; $currentLineNumber++) {
                $lineLength = strlen($fullStatusArray[$linesInFile - $currentLineNumber]);
                /** Line length is at least 1 because of new line character, so ceil should evaluate at least to 1 */
                $numberOfLinesOnDisplay = ceil($lineLength / $lineLengthLimit);
                $totalNumberOfLinesOnDisplay += $numberOfLinesOnDisplay;
                if ($numberOfLinesOnDisplay > 1) {
                    $totalLinesToRead -= $numberOfLinesOnDisplay - 1;
                    if ($totalLinesToRead < $currentLineNumber) {
                        $totalLinesToRead = $currentLineNumber;
                    }
                }
                if ($totalNumberOfLinesOnDisplay > $maxNumberOfLines) {
                    break;
                }
            }
            $slicedStatusArray = array_slice($fullStatusArray, -$totalLinesToRead, $totalLinesToRead);
            $status = implode('', $slicedStatusArray);
        }
        return $status;
    }

    /**
     * Add status update.
     *
     * @param string $text
     * @return $this
     * @throws \RuntimeException
     */
    public function add($text)
    {
        if (file_exists($this->statusFilePath) && file_get_contents($this->statusFilePath)) {
            $text = "\n{$text}";
        }
        if (false === file_put_contents($this->statusFilePath, $text, FILE_APPEND)) {
            throw new \RuntimeException('Cannot add status information to "%s"', $this->statusFilePath);
        }
        return $this;
    }

    /**
     * Clear current status.
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function clear()
    {
        if (!file_exists($this->statusFilePath)) {
            return $this;
        } else if (false === file_put_contents($this->statusFilePath, '')) {
            throw new \RuntimeException('Cannot clear status information from "%s"', $this->statusFilePath);
        }
        return $this;
    }
}