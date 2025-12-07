<?php

namespace App\Enums;

final class OrganizationType
{
    const Company = "1";
    const Institution = "2";
    const Government = "3";
    const NGO = "4";
    const Other = "5";

    public static function values()
    {
        return [
            self::Company => 'Company',
            self::Institution => 'Institution',
            self::Government => 'Government',
            self::NGO => 'NGO',
            self::Other => 'Other',
        ];
    }
}

