<?php

namespace Webkul\UVDesk\CoreBundle\Workflows\Customer;

class CreateCustomer
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
