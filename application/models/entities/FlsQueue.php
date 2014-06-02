<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * FlsQueue
 *
 * @ORM\Table(name="fls_queue")
 * @ORM\Entity
 */
class FlsQueue
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="uid", type="string", length=127, nullable=true)
     */
    private $uid;

    /**
     * @var string
     *
     * @ORM\Column(name="aid", type="string", length=255, nullable=true)
     */
    private $aid;

    /**
     * @var string
     *
     * @ORM\Column(name="loc", type="string", nullable=true)
     */
    private $loc;

    /**
     * @var boolean
     *
     * @ORM\Column(name="grant", type="boolean", nullable=false)
     */
    private $grant;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return FlsQueue
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    
        return $this;
    }

    /**
     * Get uid
     *
     * @return string 
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set aid
     *
     * @param string $aid
     * @return FlsQueue
     */
    public function setAid($aid)
    {
        $this->aid = $aid;
    
        return $this;
    }

    /**
     * Get aid
     *
     * @return string 
     */
    public function getAid()
    {
        return $this->aid;
    }

    /**
     * Set loc
     *
     * @param string $loc
     * @return FlsQueue
     */
    public function setLoc($loc)
    {
        $this->loc = $loc;
    
        return $this;
    }

    /**
     * Get loc
     *
     * @return string 
     */
    public function getLoc()
    {
        return $this->loc;
    }

    /**
     * Set grant
     *
     * @param boolean $grant
     * @return FlsQueue
     */
    public function setGrant($grant)
    {
        $this->grant = $grant;
    
        return $this;
    }

    /**
     * Get grant
     *
     * @return boolean 
     */
    public function getGrant()
    {
        return $this->grant;
    }
}