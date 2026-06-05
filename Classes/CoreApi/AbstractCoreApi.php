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

use BrainAppeal\T3monitor\CoreApi\Common\Database\Database;
use BrainAppeal\T3monitor\CoreApi\Common\Database\DatabaseInterface;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Applications;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Disc;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Extension;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\InstallTool;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Internal;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Links;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Security;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Server;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\SysLog;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractCoreApi implements CoreApiInterface {

    /**
     * @var ?int
     */
    protected $rootPageId;

    /**
     * @return DatabaseInterface
     */
    public function getDatabase(): DatabaseInterface
    {
        /** @var Database $instance */
        $instance = GeneralUtility::makeInstance(Database::class);
        return $instance;
    }

    /**
     * Creates an instance of a class taking into account the class-extensions
     *
     * @param string $className name of the class to instantiate, must not be empty
     * @param array<int, mixed> $constructorArguments Arguments for the constructor
     * @return object the created instance
     * @throws \InvalidArgumentException if class name is an empty string
     */
    public function makeInstance(string $className, ...$constructorArguments): object
    {
        return GeneralUtility::makeInstance($className, ...$constructorArguments);
    }

    public function initialize(ServerRequestInterface $request): void
    {
        $this->initializeRequest($request);
    }

    protected function initializeRequest(ServerRequestInterface $request): void
    {
        if (!isset($GLOBALS['TYPO3_REQUEST'])) {
            $GLOBALS['TYPO3_REQUEST'] = $request;
        } else {
            /** @var ServerRequestInterface $originalRequest */
            $originalRequest = $GLOBALS['TYPO3_REQUEST'];
            if (null === $originalRequest->getAttribute('normalizedParams')) {
                $normalizedParams = $request->getAttribute('normalizedParams', null);
                if (!($normalizedParams instanceof NormalizedParams)) {
                    $normalizedParams = NormalizedParams::createFromRequest($request);
                }
                $GLOBALS['TYPO3_REQUEST'] = $originalRequest->withAttribute('normalizedParams', $normalizedParams);
            }
        }
    }

    /**
     * PATH_site is deprecated in TYPO3 v10
     * => Use :php:`Environment::getPublicPath(). '/'` instead
     * @return string
     */
    public function getPublicPath(): string
    {
        return Environment::getPublicPath() . '/';
    }

    /**
     * @return string
     */
    public function getTypo3Version(): string
    {
        $cmsVersion = GeneralUtility::makeInstance(Typo3Version::class);
        /** @var Typo3Version $cmsVersion */
        return $cmsVersion->getVersion();
    }

    /**
     * Return mapping of report keys to report class names
     * @return string[]
     */
    protected function getAvailableReportsClassMap(): array
    {
        return [
            'internal' => Internal::class,
            'security' => Security::class,
            'installed_extensions' => Extension::class,
            'database' => \BrainAppeal\T3monitor\CoreApi\Common\Reports\Database::class,
            'sys_log' => SysLog::class,
            'system' => Server::class,
            'disc' => Disc::class,
            'links' => Links::class,
            'applications' => Applications::class,
            'install_tool' => InstallTool::class,
        ];
    }

    public function getReportInstances(array $params): array
    {
        $availableReports = $this->getAvailableReportsClassMap();
        return $this->createReportInstances($availableReports, $params);
    }

    /**
     * Create instances for enabled reports
     *
     * @param array $availableReports
     * @param array $params
     * @return array
     */
    final protected function createReportInstances(array $availableReports, array $params): array
    {
        $enabledReports = [];
        if(isset($params['reports'])) {
            if ($params['reports'] === 'all') {
                $enabledReports = array_keys($availableReports);
            } else {
                $enabledReports = explode(',', trim(strip_tags((string) $params['reports'])));
            }
        }
        $reportInstances = [];
        foreach($availableReports as $key => $className){
            if(in_array($key, $enabledReports, false)){
                $reportInstances[$key] = $this->makeInstance($className, $this);
            }
        }
        return $reportInstances;
    }

    /**
     * Returns the root page id for the current request/site or null if root page id was not determined before
     * @return int|null
     */
    public function getRootPageId(): ?int
    {
        if (null === $this->rootPageId) {
            $site = $this->getSite();
            $rootPageId = $site->getRootPageId();
            if ($rootPageId === 0) {
                $db = $this->getDatabase();
                $startRow = $db->getStartPage();
                if (!empty($startRow)) {
                    $rootPageId = (int) $startRow['uid'];
                }
            }
            $this->rootPageId = $rootPageId;
        }
        return $this->rootPageId;
    }

    public function getLanguageService(): LanguageService
    {
        if (!isset($GLOBALS['LANG']) || !($GLOBALS['LANG'] instanceof LanguageService)) {
            $site = $this->getSite();
            /** @var LanguageServiceFactory $languageServiceFactory */
            $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
            $siteLanguage = $site->getDefaultLanguage();
            $GLOBALS['LANG'] = $languageServiceFactory->createFromSiteLanguage($siteLanguage);
        }
        return $GLOBALS['LANG'];
    }

    public function getSite(): SiteInterface
    {
        $site = null;
        if (isset($GLOBALS['TYPO3_REQUEST'])) {
            /** @var ServerRequestInterface $request */
            $request = $GLOBALS['TYPO3_REQUEST'];
            $site = $request->getAttribute('site', null);
            if (null === $site) {
                $httpHost = GeneralUtility::getIndpEnv('HTTP_HOST');
                $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
                $site = reset($sites);
                if (count($sites) > 1) {
                    foreach ($sites as $checkSite) {
                        if ($checkSite->getBase()->getHost() === $httpHost) {
                            $site = $checkSite;
                        }
                    }
                }
                if ($site instanceof SiteInterface) {
                    $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('site', $site);
                }
            }
        }
        if (!($site instanceof SiteInterface)) {
            $site = new NullSite();
        }
        return $site;
    }

}
