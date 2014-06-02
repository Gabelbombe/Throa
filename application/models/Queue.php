<?php

Use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fls_queue")
 */
Class Queue
{
    const LOC_TWITTER   = 'tw';
    const LOC_INSTAGRAM = 'in';

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="varchar", length=100)
     */
    protected $uid;

    /**
     * @ORM\Column(type="varchar", length=100)
     */
    protected $aid;

    /**
     * @ORM\Column(type="varchar", columnDefinition="ENUM('tw', 'in')")
     */
    protected $loc;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $image;

    /**
     * @ORM\Column(type="text")
     */
    protected $tags;

    protected $comments;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    public function setLOC($location)
    {
        if (! in_array(strtolower(substr($location, 0, 2)), [self::LOC_TWITTER, self::LOC_INSTAGRAM]))
            Throw New \InvalidArgumentException("Invalid status");

        $this->loc = strtolower(substr($location, 0, 2));

        return $this;
    }
}