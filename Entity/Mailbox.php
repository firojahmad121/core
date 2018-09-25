<?php

namespace Webkul\UVDesk\TicketBundle\Entity;

/**
 * Mailbox
 */
class Mailbox
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $mailboxEmail;

    /**
     * @var boolean
     */
    private $isEnabled = false;

    /**
     * @var boolean
     */
    private $isLocalized = false;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $password;


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
     * Set name
     *
     * @param string $name
     *
     * @return Mailbox
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Mailbox
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set mailboxEmail
     *
     * @param string $mailboxEmail
     *
     * @return Mailbox
     */
    public function setMailboxEmail($mailboxEmail)
    {
        $this->mailboxEmail = $mailboxEmail;

        return $this;
    }

    /**
     * Get mailboxEmail
     *
     * @return string
     */
    public function getMailboxEmail()
    {
        return $this->mailboxEmail;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     *
     * @return Mailbox
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return boolean
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set isLocalized
     *
     * @param boolean $isLocalized
     *
     * @return Mailbox
     */
    public function setIsLocalized($isLocalized)
    {
        $this->isLocalized = $isLocalized;

        return $this;
    }

    /**
     * Get isLocalized
     *
     * @return boolean
     */
    public function getIsLocalized()
    {
        return $this->isLocalized;
    }

    /**
     * Set host
     *
     * @param string $host
     *
     * @return Mailbox
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Mailbox
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
}
