<?php 
namespace App\Helpers;

use App\Models\UserReporting;

class ReportingService
{ 
    public static function getAllJunior($userId, $allData = null, &$collectedJuniorIds = [])
    {
        static $cachedData;
        $cachedData = $cachedData ?? UserReporting::whereNull('end_date')->get()->groupBy('reporting_user_id');
        $allData = $allData ?? $cachedData;

        // Get juniors for the given user
        $juniors = $allData->get($userId, collect())->pluck('user_id')->toArray();  
        foreach ($juniors as $juniorId) {
            if (!in_array($juniorId, $collectedJuniorIds)) {
                $collectedJuniorIds[] = $juniorId;
                self::getAllJunior($juniorId, $allData, $collectedJuniorIds);
            }
        } 
        return $collectedJuniorIds;
    }


    public static function getAllSenior($userId, $allData = null, &$collectedSeniorIds = [], &$visited = [])
    {
        static $cachedData;
        $cachedData = $cachedData ?? UserReporting::whereNull('end_date')->get()->keyBy('user_id'); 
        $allData = $allData ?? $cachedData;
    
        while (isset($allData[$userId])) {
            if (in_array($userId, $visited, true)) {
                // Detected a loop/cycle; break to avoid infinite iteration
                break;
            }

            $visited[] = $userId;
            $senior = $allData[$userId];
            $userId = $senior->reporting_user_id;

            if ($userId === null) {
                break;
            }

            if (!in_array($userId, $collectedSeniorIds)) {
                $collectedSeniorIds[] = $userId;
            } else {
                // Already collected; further iteration would loop
                break;
            }
        } 
        return $collectedSeniorIds;
    }

    /**
     * Validate reporting user selection
     * 
     * @param User $user The user who is being updated
     * @param int $reportingUserId The ID of the reporting user to validate
     * @return void
     * @throws \Exception
     */
    public static function validateReportingUser($user, $reportingUserId)
    {
        $reportingUser = \App\Models\User::find($reportingUserId);
        if (!$reportingUser) {
            throw new \Exception('Reporting user not found');
        }

        // Validation: User cannot select themselves as a reporting user
        if ($user->id == $reportingUser->id) {
            throw new \Exception('You cannot select yourself as a reporting user');
        }

        // Validation: User cannot select their junior as a reporting user
        if (in_array($reportingUser->id, json_decode($user->junior_user ?? "[]"))) {
            throw new \Exception("You cannot select {$reportingUser->name} as a reporting user, as they are already your junior");
        }
    }

    /**
     * Validate referred_by user selection
     * 
     * @param User $user The user who is being updated
     * @param int|null $referredById The ID of the referred_by user to validate
     * @return void
     * @throws \Exception
     */
    public static function validateReferredBy($user, $referredById)
    {
        if ($referredById && $referredById == $user->id) {
            throw new \Exception('You cannot select yourself as referred by');
        }
    }

}
