<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email\Resources\Task;

use Webkul\UVDesk\CoreBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class TaskCreated implements UVDeskEmailTemplateInterface
{
    private static $name = 'Task Created';
    private static $subject = 'A new task #{% task.id %} is assigned to you.';
    private static $message = <<<MESSAGE
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
            <span style="font-size: 18px;">Task created - Start working on it!!</span>
        </b>
    </p>
    <p>
        <br />Hello,</p>
    <p>
        <br />
    </p>
    <p>A new task&nbsp;{%task.id%} has been&nbsp;allotted to you by&nbsp;{%task.assignUserName%}. You are requested to go through
        this link&nbsp;{%task.link%} so that you can proceed forward with the work.</p>
    <p>
        <br />
    </p>
    <p>Here go the task message:</p>
    <p>{%task.threadMessage%}
        <br />
        <br />
    </p>
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