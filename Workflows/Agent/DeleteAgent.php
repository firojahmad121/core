<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Agent;

class DeleteAgent
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
