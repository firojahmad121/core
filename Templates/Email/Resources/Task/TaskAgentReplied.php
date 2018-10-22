<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email\Resources\Task;

use Webkul\UVDesk\CoreBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class TaskAgentReplied implements UVDeskEmailTemplateInterface
{
    private static $name = 'Member Reply in Task';
    private static $subject = 'Member Reply In Task #{% task.id %}';
    private static $message = <<<MESSAGE
    <p></p>
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
        <br />Hello,</p>
    <p>
        <br />
    </p>
    <p>A member has added a reply in task #{%task.id%}. You can check the details of the task from here&nbsp;{%task.link%}.</p>
    <p>{%task.threadMessage%}{%task.attachments%}
        <br />
        <br />Thanks and Regards</p>
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