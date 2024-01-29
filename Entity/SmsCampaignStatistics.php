<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Entity;

use Diglin\Bundle\SmsCampaignBundle\Model\ExtendSmsCampaignStatistics;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * SMS Campaign Statistics.
 *
 * @ORM\Entity(repositoryClass="Diglin\Bundle\SmsCampaignBundle\Entity\Repository\SmsCampaignStatisticsRepository")
 * @ORM\Table(name="diglin_campaign_sms_stats", uniqueConstraints={
 *      @ORM\UniqueConstraint(columns={"sms_campaign_id", "marketing_list_item_id"},
 *                                                        name="diglin_sm_campaign_litem_unq")
 * })
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-bar-chart-o"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="marketing"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class SmsCampaignStatistics extends ExtendSmsCampaignStatistics implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var MarketingListItem
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\MarketingListBundle\Entity\MarketingListItem")
     * @ORM\JoinColumn(name="marketing_list_item_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $marketingListItem;

    /**
     * @var SmsCampaign
     *
     * @ORM\ManyToOne(targetEntity="Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign")
     * @ORM\JoinColumn(name="sms_campaign_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $smsCampaign;

    /**
     * @var int
     *
     * @ORM\Column(name="bounce_count", type="integer", nullable=true)
     */
    protected $bounceCount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @return MarketingListItem
     */
    public function getMarketingListItem()
    {
        return $this->marketingListItem;
    }

    /**
     * @param MarketingListItem $marketingListItem
     */
    public function setMarketingListItem(MarketingListItem $marketingListItem)
    {
        $this->marketingListItem = $marketingListItem;
    }

    public function getSmsCampaign(): SmsCampaign
    {
        return $this->smsCampaign;
    }

    /**
     * @param SmsCampaign $smsCampaign
     */
    public function setSmsCampaign(SmsCampaign $smsCampaign)
    {
        $this->smsCampaign = $smsCampaign;
    }

    public function getBounceCount(): int
    {
        return $this->bounceCount;
    }

    public function setBounceCount(int $bounceCount)
    {
        $this->bounceCount = $bounceCount;
    }

    public function incrementBounceCount()
    {
        $this->bounceCount++;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Get owner
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * Set owner
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    /**
     * Get organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * Set organization
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;
    }

    public function __toString(): string
    {
        return (string)$this->getId();
    }

    public function getId(): int
    {
        return $this->id;
    }
}
