<?php
namespace robottens\dailystatistics\helpers;

use Craft;

use craft\models\Site;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * @author    nystudio107
 * @package   Retour
 * @since     3.0.0
 */
class MultiSite
{
    // Constants
    // =========================================================================

    // Public Static Methods
    // =========================================================================

    /**
     * @param array $variables
     */
    public static function setSitesMenuVariables(array &$variables)
    {
        // Set defaults based on the section settings
        $variables['sitesMenu'] = [];
        // Enabled sites
        $sites = Craft::$app->getSites();
        if (Craft::$app->getIsMultiSite()) {
            $editableSites = $sites->getEditableSiteIds();
            /** @var Site $site */
            foreach ($sites->getAllGroups() as $group) {
                $groupSites = $sites->getSitesByGroupId($group->id);
                $variables['sitesMenu'][$group->name]
                    = ['optgroup' => $group->name];
                foreach ($groupSites as $groupSite) {
                    if (in_array($groupSite->id, $editableSites, false)) {
                        $variables['sitesMenu'][$groupSite->id] = $groupSite->name;
                    }
                }
            }
        }
    }

    /**
     * @param string $siteHandle
     * @param        $siteId
     * @param        $variables
     *
     * @throws \yii\web\ForbiddenHttpException
     */
    public static function setMultiSiteVariables($siteHandle, &$siteId, array &$variables)
    {
        // Enabled sites
        $sites = Craft::$app->getSites();
        if (Craft::$app->getIsMultiSite()) {
            // Set defaults based on the section settings
            $variables['enabledSiteIds'] = [];
            $variables['siteIds'] = [];

            /** @var Site $site */
            foreach ($sites->getEditableSiteIds() as $editableSiteId) {
                $variables['enabledSiteIds'][] = $editableSiteId;
                $variables['siteIds'][] = $editableSiteId;
            }

            // Make sure the $siteId they are trying to edit is in our array of editable sites
            if (!\in_array($siteId, $variables['enabledSiteIds'], false)) {
                if (!empty($variables['enabledSiteIds'])) {
                    if ($siteId !== 0) {
                        $siteId = reset($variables['enabledSiteIds']);
                    }
                } else {
                    self::requirePermission('editSite:'.$siteId);
                }
            }
        }
        // Set the currentSiteId and currentSiteHandle
        $variables['currentSiteId'] = empty($siteId) ? 0 : $siteId;
        $variables['currentSiteHandle'] = empty($siteHandle)
            ? Craft::$app->getSites()->currentSite->handle
            : $siteHandle;

        // Page title
        $variables['showSites'] = (
            Craft::$app->getIsMultiSite() &&
            \count($variables['enabledSiteIds'])
        );

        if ($variables['showSites']) {
            if ($variables['currentSiteId'] > 0) {
                $variables['sitesMenuLabel'] = Craft::t(
                    'site',
                    $sites->getSiteById((int)$variables['currentSiteId'])->name
                );
            }
        } else {
            $variables['currentSiteId'] = 0;
            $variables['sitesMenuLabel'] = '';
        }
    }

    /**
     * Return a siteId from a siteHandle
     *
     * @param string $siteHandle
     *
     * @return int|null
     * @throws NotFoundHttpException
     */
    public static function getSiteIdFromHandle($siteHandle)
    {
        // Get the site to edit
        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
            if (!$site) {
                throw new NotFoundHttpException('Invalid site handle: '.$siteHandle);
            }
            $siteId = $site->id;
        } else {
            $site = Craft::$app->getSites()->getPrimarySite();
            $siteId = $site->id;
        }

        return $siteId;
    }

    /**
     * @param string $permissionName
     *
     * @throws ForbiddenHttpException
     */
    public static function requirePermission(string $permissionName)
    {
        if (!Craft::$app->getUser()->checkPermission($permissionName)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    // Protected Static Methods
    // =========================================================================
}
