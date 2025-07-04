<?php

namespace App\Enums;

final class EducationEnam
{
    const PSC       = "1";
    const JSC       = "2";
    const SSC       = "3";
    const HSC       = "4";
    const Bachelor  = "5";
    const Masters   = "6";
    const PhD       = "7";

    public static function values()
    {
        return [
            self::PSC       => 'PSC',
            self::JSC       => 'JSC',
            self::SSC       => 'SSC',
            self::HSC       => 'HSC',
            self::Bachelor  => 'Bachelor',
            self::Masters   => 'Masters',
            self::PhD       => 'PhD',
        ];
    }
}
