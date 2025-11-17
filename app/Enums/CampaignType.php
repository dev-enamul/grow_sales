<?php

namespace App\Enums;

final class CampaignType
{
    const Online = "1";
    const Offline = "2";
    const SocialMedia = "3";
    const Email = "4";
    const SMS = "5";
    const Print = "6";
    const TV = "7";
    const Radio = "8";
    const Outdoor = "9";
    const Event = "10";
    const Referral = "11";
    const Other = "12";

    public static function values()
    {
        return [
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::SocialMedia => 'Social Media',
            self::Email => 'Email',
            self::SMS => 'SMS',
            self::Print => 'Print',
            self::TV => 'TV',
            self::Radio => 'Radio',
            self::Outdoor => 'Outdoor',
            self::Event => 'Event',
            self::Referral => 'Referral',
            self::Other => 'Other',
        ];
    }
}

