<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Agent;

class CreateAgent
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
