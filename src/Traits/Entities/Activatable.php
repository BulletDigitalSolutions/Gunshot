<?php

namespace BulletDigitalSolutions\Gunshot\Traits\Entities;

use Doctrine\ORM\Mapping as ORM;

trait Activatable
{
    /**
     * @ORM\Column(type="boolean", options={"default":1})
     */
    #[ORM\Column(type: 'boolean', options: ['default' => 1])]
    protected $isActive = true;

    /**
     * @return bool
     */
    public function isActive()
    {
        return 1 == $this->isActive;
    }

    /**
     * @param  mixed  $isActive
     */
    public function setIsActive($isActive): void
    {
        $this->isActive = $isActive;
    }
}
