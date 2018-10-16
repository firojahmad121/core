<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email\Resources\Ticket;

use Webkul\UVDesk\CoreBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class CustomerTicketCreated implements UVDeskEmailTemplateInterface
{
    private static $name = 'Ticket Generate Success Mail For Customer';
    private static $subject = 'Congo!! Ticket generation process accomplished.';
    private static $message = <<<MESSAGE
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
            <span style="font-size: 18px;">Ticket generated!!</span>
        </b>
    </p>
    <p>
        <br />Hello {%ticket.customerName%},</p>
    <p>
        <br />
    </p>
    <p>Your ticket #{%ticket.id%} has been generated successfully. You can check this ticket through this link&nbsp;{%ticket.link%}
        and you can also reply via this email.</p>
    <p>
        <br />
    </p>
    <p>Our staff will get back to you soon with the possible solution for your support request.
        <br />
    </p>
    <p>If you have more query then feel free to ask. We will be more than happy to help you in resolving your issue.&nbsp;</p>
    <p>
        <br />
    </p>
    <p>Thanks and Regards
        <br />
    </p>
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