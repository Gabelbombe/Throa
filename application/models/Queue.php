<?php

Use \Doctrine\ORM\Mapping AS ORM;

/**
 * Queue
 *
 * @ORM\Table(name="fls_queue")
 * @ORM\Entity
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
     * @ORM\Column(type="bool")
     */
    protected $grant;

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

    public function __construct()
    {
        // ....
    }

    public function setGrant($grant)
    {
        if (! is_bool($grant))  Throw New \InvalidArgumentException("Invalid grant type");

            $this->grant = $grant;

        return $this;
    }

    public function setUID($uid)
    {
        // some type of filter?

            $this->uid = $uid;

        return $this;
    }

    public function setAID($aid)
    {
        // some type of filter?

            $this->aid = $aid;

        return $this;
    }

    public function setLOC($location)
    {
        if (! in_array(strtolower(substr($location, 0, 2)), [self::LOC_TWITTER, self::LOC_INSTAGRAM]))
            Throw New \InvalidArgumentException("Invalid location");

        $this->loc = strtolower(substr($location, 0, 2));

        return $this;
    }
}