<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email\Resources\Ticket;

use Webkul\UVDesk\CoreBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class MailCollaborator implements UVDeskEmailTemplateInterface
{
    private static $name = 'Mail to the collaborator';
    private static $subject = 'Mail to the collaborator';
    private static $message = <<<MESSAGE
    <p></p>
    <p></p>
    <p style="text-align: center; ">{%global.companyLogo%}
        <br />
    </p>
    <p>
        <br />Hello&nbsp;{%ticket.collaboratorName%}
        <span style="line-height: 1.42857143;">,</span>
    </p>
    <p>
        <span style="line-height: 1.42857143;">
            <br />
        </span>
    </p>
    <p>You are added as a collaborator in the ticket #{%ticket.id%}. You can check the ticket from this link&nbsp;{%ticket.link%}.</p>
    <p>
        <br />
    </p>
    <p>Thanks and Regards</p>
    <p>{%global.companyName%}
        <br />
    </p>
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