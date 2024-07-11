<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Entity;

use Diglin\Bundle\SmsCampaignBundle\Model\ExtendSmsCampaign;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CampaignBundle\Entity\Campaign;
use Oro\Bundle\CampaignBundle\Entity\TransportSettings;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

#[ORM\Entity(repositoryClass: \Diglin\Bundle\SmsCampaignBundle\Entity\Repository\SmsCampaignRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-envelope'], 'ownership' => ['owner_type' => 'USER', 'owner_field_name' => 'owner', 'owner_column_name' => 'owner_id', 'organization_field_name' => 'organization', 'organization_column_name' => 'organization_id'], 'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing'], 'grid' => ['default' => 'oro-sms-campaign-grid'], 'tag' => ['enabled' => true]])]
#[ORM\Table(name: 'diglin_campaign_sms')]
#[ORM\Index(name: 'cmpgn_sms_owner_idx', columns: ['owner_id'])]
class SmsCampaign implements ExtendEntityInterface
{
    use \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
    use ExtendEntityTrait;

    const SCHEDULE_MANUAL = 'manual';
    const SCHEDULE_DEFERRED = 'deferred';

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    protected $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'text', type: 'text', nullable: true)]
    protected $text;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_sent', type: 'boolean')]
    protected $sent = false;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'sent_at', type: 'datetime', nullable: true)]
    protected $sentAt;

    /**
     * @var string
     */
    #[ORM\Column(name: 'schedule', type: 'string', length: 255)]
    protected $schedule;

    /**
     * @var ?\DateTime
     */
    #[ORM\Column(name: 'scheduled_for', type: 'datetime', nullable: true)]
    protected $scheduledFor;

    /**
     * @var Campaign
     */
    #[ORM\ManyToOne(targetEntity: \Oro\Bundle\CampaignBundle\Entity\Campaign::class)]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    protected $campaign;

    /**
     * @var MarketingList
     */
    #[ORM\ManyToOne(targetEntity: \Oro\Bundle\MarketingListBundle\Entity\MarketingList::class)]
    #[ORM\JoinColumn(name: 'marketing_list_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected $marketingList;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: \Oro\Bundle\UserBundle\Entity\User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected $owner;

    /**
     * @var string
     */
    #[ORM\Column(name: 'transport', type: 'string', length: 255, nullable: false)]
    protected $transport;

    /**
     * @var TransportSettings
     */
    #[ORM\OneToOne(targetEntity: \Oro\Bundle\CampaignBundle\Entity\TransportSettings::class, cascade: ['all'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'transport_settings_id', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    protected $transportSettings;

    /**
     * @var Organization
     */
    #[ORM\ManyToOne(targetEntity: \Oro\Bundle\OrganizationBundle\Entity\Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected $organization;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected $createdAt;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected $updatedAt;

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getEntityName(): ?string
    {
        if ($this->marketingList) {
            return $this->marketingList->getEntity();
        }

        return null;
    }

    /**
     * Get id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get updatedAt
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set campaign
     */
    public function setCampaign(?Campaign $campaign = null)
    {
        $this->campaign = $campaign;
    }

    /**
     * Get campaign
     */
    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    /**
     * Set marketingList
     */
    public function setMarketingList(MarketingList $marketingList)
    {
        $this->marketingList = $marketingList;
    }

    /**
     * Get marketingList
     */
    public function getMarketingList(): ?MarketingList
    {
        return $this->marketingList;
    }

    /**
     * Set owner
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    /**
     * Get owner
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text)
    {
        $this->text = $text;
    }

    /**
     * Set sent
     */
    public function setSent(bool $sent)
    {
        $this->sent = $sent;
        $this->sentAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Get isSent
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Set schedule
     */
    public function setSchedule(string $schedule)
    {
        $types = [self::SCHEDULE_MANUAL, self::SCHEDULE_DEFERRED];

        if (!in_array($schedule, $types)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Schedule type %s is not know. Known types are %s',
                    $schedule,
                    implode(', ', $types)
                )
            );
        }
        $this->schedule = $schedule;
    }

    /**
     * Get schedule
     */
    public function getSchedule(): ?string
    {
        return $this->schedule;
    }

    public function getScheduledFor(): ?\DateTime
    {
        return $this->scheduledFor;
    }

    public function setScheduledFor(?\DateTime $scheduledFor)
    {
        $this->scheduledFor = $scheduledFor;
    }

    /**
     * Set sentAt
     */
    public function setSentAt(?\DateTime $sentAt)
    {
        $this->sentAt = $sentAt;
    }

    /**
     * Get sentAt
     */
    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    public function getTransport(): ?string
    {
        return $this->transport;
    }

    public function setTransport(string $transport)
    {
        $this->transport = $transport;
    }

    public function getTransportSettings(): ?TransportSettings
    {
        return $this->transportSettings;
    }

    public function setTransportSettings(?TransportSettings $transportSettings)
    {
        $this->transportSettings = $transportSettings;
    }

    /**
     * Set organization
     */
    public function setOrganization(?Organization $organization = null)
    {
        $this->organization = $organization;
    }

    /**
     * Get organization
     */
    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function __toString(): string
    {
        return (string)$this->getName();
    }
}
