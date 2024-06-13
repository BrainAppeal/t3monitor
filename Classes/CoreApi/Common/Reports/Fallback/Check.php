<?php

namespace BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback;


use ReflectionClass;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Install\SystemEnvironment\Check as CoreInstallCheck;

/**
 * A class representing a certain status
 */
class Check extends CoreInstallCheck
{
    public function getStatusList(): array
    {
        $statusList = [];
        $class = new ReflectionClass($this);
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (strpos($methodName, 'check') === 0 && empty($method->getParameters()) && !$method->isPrivate()) {
                $this->$methodName();
                $this->addMethodStatusMessages($statusList, $methodName);
            }
        }

        $methodName = 'checkPhpExtension';
        if (method_exists($this, $methodName)) {
            foreach ($this->requiredPhpExtensions as $extension) {
                $this->checkPhpExtension($extension);
            }
            foreach ($this->suggestedPhpExtensions as $extension => $purpose) {
                $this->checkPhpExtension($extension, false, $purpose);
            }
            $this->addMethodStatusMessages($statusList, $methodName);
        }

        if (isset($statusList['checkPhpVersion'])) {
            $statusList['Php'] = $statusList['checkPhpVersion'];
            $statusList['Php']['value'] = PHP_VERSION;
            unset($statusList['checkPhpVersion']);
        } else {
            $statusList['Php'] = [
                'value' => PHP_VERSION,
                'severity' => Status::OK,
            ];
        }
        $memoryLimit = ini_get('memory_limit');
        if (isset($statusList['checkMemorySettings'])) {
            $statusList['PhpMemoryLimit'] = $statusList['checkMemorySettings'];
            $statusList['PhpMemoryLimit']['value'] = $memoryLimit;
            unset($statusList['checkMemorySettings']);
        } else {
            $statusList['PhpMemoryLimit'] = [
                'value' => $memoryLimit,
                'severity' => Status::OK,
            ];
        }

        $statusList['Webserver'] = array(
            'value' => $_SERVER['SERVER_SOFTWARE'],
            'severity' => Status::OK,
        );
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
