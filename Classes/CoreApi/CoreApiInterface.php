<?php
/**
 * t3monitor comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2023 Brain Appeal GmbH
 *
 * @copyright 2023 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.t3monitor.de/
 */

namespace BrainAppeal\T3monitor\CoreApi;

use BrainAppeal\T3monitor\CoreApi\Common\Database\DatabaseInterface;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\AbstractReport;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

interface CoreApiInterface
{
    /**
     * Creates an instance of a class taking into account the class-extensions
     * Wrapper function for \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance
     * Needed for custom object initialization for objects, depending on TYPO3 version
     *
     * @param string $className name of the class to instantiate, must not be empty
     * @param array<int, mixed> $constructorArguments Arguments for the constructor
     * @return object the created instance
     * @throws \InvalidArgumentException if class name is an empty string
     */
    public function makeInstance(string $className, ...$constructorArguments): object;

    public function initialize(ServerRequestInterface $request): void;

    /**
     * Returns the report instances
     * @param array $params
     * @return AbstractReport[]|array
     */
    public function getReportInstances(array $params): array;

    /**
     * @return DatabaseInterface
     */
    public function getDatabase(): DatabaseInterface;

    /**
     * Returns the root page id for the current request/site or null if root page id was not determined before
     * @return int|null
     */
    public function getRootPageId(): ?int;
    public function getLanguageService(): LanguageService;
    public function getSite(): SiteInterface;

    public function getTypo3Version(): string;
}
