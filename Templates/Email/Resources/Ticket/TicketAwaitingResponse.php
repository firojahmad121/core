<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email\Resources\Ticket;

use Webkul\UVDesk\CoreBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class TicketAwaitingResponse implements UVDeskEmailTemplateInterface
{
    private static $name = 'Long Pending Ticket';
    private static $subject = 'Waiting for response on the ticket.';
    private static $message = <<<MESSAGE
    <p></p>
    <p></p>
    <p></p>
    <p></p>
    <p></p>
    <p></p>
    <p></p>
    <p style="text-align: center; ">{%global.companyLogo%}</p>
    <p style="text-align: center; ">
        <br />
    </p>
    <p style="text-align: center; ">
        <b>
            <span style="font-size: 18px;">Response awaited!!</span>
        </b>
    </p>
    <p style="text-align: center; ">
        <br />
    </p>
    <p>Hi {%ticket.customerName%},
        <br />
    </p>
    <p>
        <br />
    </p>
    <p>This message is in reference to your ticket id
        <b>#{%ticket.id%}</b>. You have not reverted back since the last couple of days and due to this, we are unable to establish
        a single point of connection with you. We are considering this issue resolved and we have closed this ticket.</p>
    <p>
        <br />
    </p>
    <p>
        <b>Ticket Id&nbsp; :</b>&nbsp; #{%ticket.id%}</p>
    <p>
        <b>Subject&nbsp; :</b>&nbsp; {%ticket.subject%}</p>
    <p>
        <b>Status&nbsp; :&nbsp;</b> {%ticket.status%}</p>
    <br />
    <p style="margin-bottom: 0cm; line-height: 100%">
        <font style="font-size: 18pt" size="5">
            <span style="font-size: 14px;">If you want to reopen this ticket, just respond to this mail or you can reply to this ticket online at </span>
        </font>{%ticket.link%}
        <font style="font-size: 18pt" size="5">
            <span style="font-size: 14px;">
                <br />
            </span>
        </font>
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
    <p></p>
    <p></p>
    <p></p>
    <p></p>
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