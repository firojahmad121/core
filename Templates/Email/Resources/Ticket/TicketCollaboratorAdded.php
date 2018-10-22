<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email\Resources\Ticket;

use Webkul\UVDesk\CoreBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class TicketCollaboratorAdded implements UVDeskEmailTemplateInterface
{
    private static $name = 'Collaborator added to ticket';
    private static $subject = 'A new Collaborator have been added.';
    private static $message = <<<MESSAGE
    <p></p>
    <p></p>
    <p style="text-align: center; ">{%global.companyLogo%}</p>
    <p style="text-align: center; ">
        <br />
    </p>
    <p style="text-align: center; ">
        <b>
            <span style="font-size: 18px;">Collaborator added!!</span>
        </b>
    </p>
    <p style="text-align: center; ">
        <br />
    </p> Hello {%ticket.agentName%},
    <p></p>
    <p></p>
    <p>
        <br />
    </p>
    <p>
        <span style="line-height: 1.42857;">A collaborator is added</span>
        <span style="line-height: 1.42857;">&nbsp;in the ticket #{%ticket.id%}. You can check the ticket from this link&nbsp;{%ticket.link%}.</span>
    </p>
    <p>
        <span style="line-height: 1.42857;">
            <br />
        </span>
    </p>
    <p></p>
    <p>Thanks and Regards</p>
    <p>{%global.companyName%}
        <br />
    </p>
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