<?php

namespace Webkul\UVDesk\CoreBundle\Templates\Email\Resources\Account;

use Webkul\UVDesk\CoreBundle\Templates\Email\UVDeskEmailTemplateInterface;

abstract class CustomerForgotPassword implements UVDeskEmailTemplateInterface
{
    private static $name = 'Customer Forgot password';
    private static $subject = 'Customer Forgot password';
    private static $message = <<<MESSAGE
    <p></p>
    <p></p>
    <p style="text-align: center; ">
        <span style="line-height: 1.42857;">{%global.companyLogo%}</span>
    </p>
    <p style="text-align: center; ">
        <span style="line-height: 1.42857;">
            <br />
        </span>
    </p>
    <p style="text-align: center; ">
        <span style="line-height: 1.42857;">
            <b>
                <span style="font-size: 18px;">Forgot password, this is it!!</span>
            </b>
            <br />
        </span>
        <br />
    </p>
    <p align="left" style="text-align: center; margin-bottom: 0cm; line-height: 100%;">
        <br />
    </p>
    <p>Hi&nbsp;{%user.userName%}</p>
    <p>
        <br />
    </p>
    <p>You recently requested to reset your password for your {%global.companyName%} account. Click the link to reset it&nbsp;{%user.forgotPasswordLink%}</p>
    <p>
        <br />
    </p>
    <p>If you did not request a password reset, please ignore this mail or revert back to let us know.</p>
    <p>
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