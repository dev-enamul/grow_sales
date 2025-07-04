<?php

namespace App\Http\Controllers\Common;

use App\Enums\BloodGroup;
use App\Enums\EducationEnam;
use App\Enums\Gender;
use App\Enums\MaritualStatus;
use App\Enums\Priority;
use App\Enums\ProfessionEnam;
use App\Enums\Religion;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EnamController extends Controller
{
    public function bloodgroup()
    {
        $groups = BloodGroup::values();

        $result = [];

        foreach ($groups as $id => $name) {
            $result[] = [
                'id' => $id,
                'name' => $name,
            ];
        }
        return success_response($result);
    }

    public function gender()
    {
        $genders = Gender::values();

        $result = [];

        foreach ($genders as $id => $name) {
            $result[] = [
                'id' => $id,
                'name' => $name,
            ];
        }
        return success_response($result);
    }

    public function maritualStatus()
    {
        $statuses = MaritualStatus::values();

        $result = [];

        foreach ($statuses as $id => $name) {
            $result[] = [
                'id' => $id,
                'name' => $name,
            ];
        }
        return success_response($result);
    }

    public function priority()
    {
        $priorities = Priority::values();

        $result = [];

        foreach ($priorities as $id => $name) {
            $result[] = [
                'id' => $id,
                'name' => $name,
            ];
        }
        return success_response($result);
    }

    public function religion()
    {
        $religions = Religion::values();

        $result = [];

        foreach ($religions as $id => $name) {
            $result[] = [
                'id' => $id,
                'name' => $name,
            ];
        }
        return success_response($result);
    }

    public function status()
    {
        $statuses = Status::values();

        $result = [];

        foreach ($statuses as $id => $name) {
            $result[] = [
                'id' => $id,
                'name' => $name,
            ];
        }
        return success_response($result);
    }

    public function education()
    {
        $educations = EducationEnam::values();

        $result = [];

        foreach ($educations as $id => $name) {
            $result[] = [
                'id' => $id,
                'name' => $name,
            ];
        }
        return success_response($result);
    }

    public function profession()
    {
        $professions = ProfessionEnam::values();

        $result = [];

        foreach ($professions as $id => $name) {
            $result[] = [
                'id' => $id,
                'name' => $name,
            ];
        }
        return success_response($result);
    }
}
