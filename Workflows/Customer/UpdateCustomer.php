<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Customer;

class UpdateCustomer
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
