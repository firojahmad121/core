<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email\Resources\Task;

use Webkul\UVDesk\CoreBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class TaskMemberAdded implements UVDeskEmailTemplateInterface
{
    private static $name = 'Member Added In Task';
    private static $subject = 'Member Added In Task';
    private static $message = <<<MESSAGE
    <p></p>
    <p></p>
    <p style="text-align: center; ">{%global.companyLogo%}</p>
    <p style="text-align: center; ">
        <br />
    </p>
    <p>Hello&nbsp;{%user.userName%},</p>
    <p>
        <br />
    </p>
    <p>You are added in the task #{%task.id%}. You can check the details of the task from this link {%task.link%}</p>
    <p>
        <br />
    </p>
    <p>Thanks and Regards</p>
    <p>{%global.companyName%}
        <br />
    </p>
    <p>
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