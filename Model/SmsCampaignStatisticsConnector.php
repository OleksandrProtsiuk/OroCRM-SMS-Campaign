<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Model;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaignStatistics;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListItem;
use Oro\Bundle\MarketingListBundle\Model\MarketingListItemConnector;

class SmsCampaignStatisticsConnector
{
    /**
     * @var MarketingListItemConnector
     */
    protected $marketingListItemConnector;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var MarketingListItem[]
     */
    protected $marketingListItemCache = [];

    /**
     * @var SmsCampaignStatistics[]
     */
    protected $statisticRecordsCache = [];

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @param MarketingListItemConnector $marketingListItemConnector
     * @param DoctrineHelper             $doctrineHelper
     */
    public function __construct(
        MarketingListItemConnector $marketingListItemConnector,
        DoctrineHelper $doctrineHelper
    ) {
        $this->marketingListItemConnector = $marketingListItemConnector;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @param SmsCampaign $smsCampaign
     * @param object      $entity
     *
     * @return SmsCampaignStatistics
     */
    public function getStatisticsRecord(SmsCampaign $smsCampaign, $entity)
    {
        $marketingList = $smsCampaign->getMarketingList();
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        /**
         * Cache was added because there is a case:
         * - 2 SMS campaigns linked to one marketing list
         * - statistic can created using batches (marketing list item connector will be used)
         *  and flush will be run once per N records creation
         * in this case same Marketing list entity will be received twice for one marketing list
         * and new MarketingListItem for same $marketingList and $entityId will be persisted twice.
         *
         * Marketing list name used as key for cache because Id can be empty and name is unique
         *
         */
        if (empty($this->marketingListItemCache[$marketingList->getName()][$entityId])) {
            // Mark marketing list item as contacted
            $this->marketingListItemCache[$marketingList->getName()][$entityId] = $this->marketingListItemConnector
                ->getMarketingListItem($marketingList, $entityId);
        }
        /** @var MarketingListItem $marketingListItem */
        $marketingListItem = $this->marketingListItemCache[$marketingList->getName()][$entityId];
        $marketingListItemHash = spl_object_hash($marketingListItem);

        $manager = $this->doctrineHelper->getEntityManager($this->entityName);

        $statisticsRecord = null;
        if ($marketingListItem->getId() !== null) {
            $statisticsRecord = $manager->getRepository($this->entityName)
                ->findOneBy(['smsCampaign' => $smsCampaign, 'marketingListItem' => $marketingListItem]);
        }

        if (!empty($this->statisticRecordsCache[$smsCampaign->getId()][$marketingListItemHash])) {
            $statisticsRecord = $this->statisticRecordsCache[$smsCampaign->getId()][$marketingListItemHash];
        }

        if (!$statisticsRecord) {
            $statisticsRecord = new SmsCampaignStatistics();
            $statisticsRecord->setSmsCampaign($smsCampaign);
            $statisticsRecord->setMarketingListItem($marketingListItem);
            $statisticsRecord->setOwner($smsCampaign->getOwner());
            $statisticsRecord->setOrganization($smsCampaign->getOrganization());

            $this->statisticRecordsCache[$smsCampaign->getId()][$marketingListItemHash] = $statisticsRecord;
            $manager->persist($statisticsRecord);
        }

        return $statisticsRecord;
    }

    /**
     * Method must be called on onClear Doctrine event, because after clear entity manager
     * cached entities will be detached
     */
    public function clearMarketingListItemCache()
    {
        $this->marketingListItemCache = [];
        $this->statisticRecordsCache = [];
    }
}
