<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email\Resources\Ticket;

use Webkul\UVDesk\CoreBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class TicketCollaboratorReplied implements UVDeskEmailTemplateInterface
{
    private static $name = 'Collaborator added reply';
    private static $subject = 'Ticket collaborator added reply in ticket {%ticket.id%}';
    private static $message = <<<MESSAGE
    <p></p>
    <p></p>
    <p style="text-align: center; ">{%global.companyLogo%}</p>
    <p style="text-align: center; ">
        <br />
    </p>
    <p style="text-align: center; ">
        <b>
            <span style="font-size: 18px;">New conversation!!</span>
        </b>
    </p>
    <p>
        <br />Hello {%ticket.agentName%},</p>
    <p>
        <br />
    </p>
    <p></p>
    <p>
        <span style="line-height: 1.42857143;">Collaborator of the ticket #{%ticket.id%} has added a reply. You can check the ticket from here&nbsp;</span>{%ticket.link%}</p>
    <p>
        <br />
    </p>
    <p>Here go the message:</p>
    <p>{%ticket.threadMessage%}
        <br />
    </p>
    <p>
        <br />
    </p>
    <p>Thanks and Regards</p>
    <p>{%global.companyName%}
        <br />
    </p>
    <p></p>
    <p></p>
    <p></p>
MESSAGE;

    public static function getName()
    {
        return self::$name;
    }

    public static function getSubject()
    {
        return self::$subject;
    }

    public static function getMessage()
    {
        return self::$message;
    }
}