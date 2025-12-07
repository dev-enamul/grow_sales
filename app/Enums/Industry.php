<?php

namespace App\Enums;

final class Industry
{
    const Technology = "1";
    const Healthcare = "2";
    const Finance = "3";
    const Education = "4";
    const Manufacturing = "5";
    const Retail = "6";
    const RealEstate = "7";
    const Construction = "8";
    const Agriculture = "9";
    const Transportation = "10";
    const Hospitality = "11";
    const Media = "12";
    const Energy = "13";
    const Consulting = "14";
    const Legal = "15";
    const Other = "16";

    public static function values()
    {
        return [
            self::Technology => 'Technology',
            self::Healthcare => 'Healthcare',
            self::Finance => 'Finance',
            self::Education => 'Education',
            self::Manufacturing => 'Manufacturing',
            self::Retail => 'Retail',
            self::RealEstate => 'Real Estate',
            self::Construction => 'Construction',
            self::Agriculture => 'Agriculture',
            self::Transportation => 'Transportation',
            self::Hospitality => 'Hospitality',
            self::Media => 'Media',
            self::Energy => 'Energy',
            self::Consulting => 'Consulting',
            self::Legal => 'Legal',
            self::Other => 'Other',
        ];
    }
}

