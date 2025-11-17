<?php

namespace App\Enums;

final class Channel
{
    const Facebook = "1";
    const Instagram = "2";
    const Google = "3";
    const YouTube = "4";
    const LinkedIn = "5";
    const Twitter = "6";
    const TikTok = "7";
    const WhatsApp = "8";
    const Email = "9";
    const SMS = "10";
    const TV = "11";
    const Radio = "12";
    const Newspaper = "13";
    const Magazine = "14";
    const Billboard = "15";
    const Website = "16";
    const Blog = "17";
    const Podcast = "18";
    const Referral = "19";
    const Direct = "20";
    const Other = "21";

    public static function values()
    {
        return [
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::Google => 'Google',
            self::YouTube => 'YouTube',
            self::LinkedIn => 'LinkedIn',
            self::Twitter => 'Twitter',
            self::TikTok => 'TikTok',
            self::WhatsApp => 'WhatsApp',
            self::Email => 'Email',
            self::SMS => 'SMS',
            self::TV => 'TV',
            self::Radio => 'Radio',
            self::Newspaper => 'Newspaper',
            self::Magazine => 'Magazine',
            self::Billboard => 'Billboard',
            self::Website => 'Website',
            self::Blog => 'Blog',
            self::Podcast => 'Podcast',
            self::Referral => 'Referral',
            self::Direct => 'Direct',
            self::Other => 'Other',
        ];
    }
}

