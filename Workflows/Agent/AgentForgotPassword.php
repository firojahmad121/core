<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Agent;

class AgentForgotPassword
{
    public static function getAlias()
    {
        return 'ticket.reply.added.agent';
    }

    public static function getAliasedGroup()
    {
        return 'ticket';
    }
}
