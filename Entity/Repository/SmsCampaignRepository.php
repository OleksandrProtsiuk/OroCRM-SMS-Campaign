<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Entity\Repository;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class SmsCampaignRepository extends EntityRepository
{
    /**
     * @return SmsCampaign[]
     */
    public function findSmsCampaignsToSend()
    {
        $qb = $this->prepareSmsCampaignsToSendQuery();
        $qb->select('sms_campaign');

        return $qb->getQuery()->getResult();
    }

    protected function prepareSmsCampaignsToSendQuery(): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from('SmsCampaignBundle:SmsCampaign', 'sms_campaign')
            ->where($qb->expr()->eq('sms_campaign.sent', ':sent'))
            ->andWhere($qb->expr()->eq('sms_campaign.schedule', ':scheduleType'))
            ->andWhere($qb->expr()->isNotNull('sms_campaign.scheduledFor'))
            ->andWhere($qb->expr()->lte('sms_campaign.scheduledFor', ':currentTimestamp'))
            ->setParameter('sent', false, Types::BOOLEAN)
            ->setParameter('scheduleType', SmsCampaign::SCHEDULE_DEFERRED, Types::STRING)
            ->setParameter('currentTimestamp', new \DateTime('now', new \DateTimeZone('UTC')), Types::DATETIME_MUTABLE);

        return $qb;
    }

    public function countSmsCampaignsToSend(): int
    {
        $qb = $this->prepareSmsCampaignsToSendQuery();
        $qb->select('COUNT(sms_campaign.id)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}
