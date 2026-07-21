<?php

namespace BulletDigitalSolutions\Gunshot\Traits\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait Timestampable
{
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     *
     * @var DateTime
     */
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     *
     * @var DateTime
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    protected $updatedAt;

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param  DateTime  $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @param  DateTime  $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }
}
