<?php

namespace BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback;


use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Install\Exception;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck as CoreDatabaseCheck;

/**
 * A class representing a certain status
 */
class DatabaseCheck extends CoreDatabaseCheck
{
    /**
     * @var FlashMessageQueue
     */
    private $messageQueue;

    public function getStatusList(): array
    {
        $this->messageQueue = new FlashMessageQueue('install-database-check');
        $statusList = [];
        $installedDrivers = $this->identifyInstalledDatabaseDriver();

        // check requirements of database platform for installed driver
        foreach ($installedDrivers as $driver) {
            try {
                $this->messageQueue = $this->checkDatabasePlatformRequirements($driver);
            } catch (Exception $exception) {
                $this->messageQueue->enqueue(
                    new FlashMessage(
                        '',
                        $exception->getMessage(),
                        Status::INFO
                    )
                );
            }
        }
        $this->addMethodStatusMessages($statusList, 'checkDatabasePlatformRequirements');

        // check requirements of database driver for installed driver
        foreach ($installedDrivers as $driver) {
            try {
                $this->messageQueue = $this->checkDatabaseDriverRequirements($driver);
            } catch (Exception $exception) {
                $this->messageQueue->enqueue(
                    new FlashMessage(
                        '',
                        $exception->getMessage(),
                        Status::INFO
                    )
                );
            }
        }
        $this->addMethodStatusMessages($statusList, 'checkDatabaseDriverRequirements');
        return $statusList;
    }

    protected function addMethodStatusMessages(array &$statusList, string $methodName): void
    {
        $maxSeverity = Status::NOTICE;
        $messageTexts = [];
        foreach ($this->messageQueue as $message) {
            /** @var AbstractMessage $message */
            $severity = Status::getSeverityAsInt($message->getSeverity());
            if ($severity > $maxSeverity) {
                $maxSeverity = $severity;
            }
            $messageTexts[] = $message->getTitle() . ': ' . $message->getMessage() . '  ['.$severity.']';
        }
        $this->messageQueue->clear();
        $statusList[$methodName] = [
            'value' => '',
            'severity' => $maxSeverity,
            'message' => implode("\n", $messageTexts),
        ];
    }
}
