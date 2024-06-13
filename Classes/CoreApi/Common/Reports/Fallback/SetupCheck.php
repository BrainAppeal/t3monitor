<?php

namespace BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback;


use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use \TYPO3\CMS\Install\SystemEnvironment\SetupCheck as CoreSetupCheck;

/**
 * A class representing a certain status
 */
class SetupCheck extends CoreSetupCheck
{
    public function getStatusList(): array
    {
        $this->messageQueue = new FlashMessageQueue('install');
        $statusList = array();
        $class = new \ReflectionClass($this);
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (strpos($methodName, 'check') === 0 && empty($method->getParameters()) && !$method->isPrivate()) {
                $this->$methodName();
                $this->addMethodStatusMessages($statusList, $methodName);
            }
        }

        $methodName = 'isTrueTypeFontWorking';
        if (method_exists($this, $methodName)) {
            $this->isTrueTypeFontWorking();
            $this->addMethodStatusMessages($statusList, $methodName);
        }
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
