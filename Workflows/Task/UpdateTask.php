<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Task;

class UpdateTask
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
