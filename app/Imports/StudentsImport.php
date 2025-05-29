<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Str;
use App\Models\Studentclass;
use App\Models\Studenthouse;
use App\Models\StudentStatus;
use App\Models\Studentpicture;
use App\Models\PromotionStatus;
use App\Models\StudentBatchModel;
use App\Models\ParentRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Studentpersonalityprofile;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;

class StudentsImport implements ToModel, WithProgressBar, WithStartRow, WithUpsertColumns, WithUpserts, WithValidation
{
    use Importable;

    public $id = 0;

    public $_sclassid = 0;

    public $_teremid = 0;

    public $_sessionid = 0;

    public $_batchid = 0;

    public function model(array $row)
    {
        $current = 'Current';
        $schoolclassid = Session::get('sclassid');
        $termid = Session::get('tid');
        $sessionid = Session::get('sid');
        $batchid = Session::get('batchid');

        $admissionno = $row[0];
        $surname = $row[1];
        $firstname = $row[2];
        $othername = $row[3];
        $gender = $row[4];
        $homeaddress = $row[5];
        $dob = $row[6];
        $age = $row[7];
        $placeofbirth = $row[8];
        $nationality = $row[9];
        $state = $row[10];
        $local = $row[11];
        $religion = $row[12];
        $lastschool = $row[13];
        $lastclass = $row[14];


        $father_title = Str::limit($row[18], 3, '');
        $father = Str::substr($row[18], 3);
        $father_phone = $row[19];
        $office_address = $row[20];
        $father_occupation = $row[21];
        $mother_title = Str::limit($row[22], 3, '');
        $mother = Str::substr($row[22], 3);
        $mother_phone = $row[23];
        $mother_occupation = $row[24];
        $mother_office_address = $row[25];
        $parent_address = $row[26];
        $parent_religion = $row[27];




        $studentbiodata = new Student();
        $studentclass = new Studentclass();
        $promotion = new PromotionStatus();
        $parent = new ParentRegistration();
        $studenthouse = new Studenthouse();
        $picture = new Studentpicture();
        $studentpersonalityprofile = new Studentpersonalityprofile();
        $studentStatus = StudentStatus::where('status', 'old')->first(); // Example query


        //populating student biodata
        $studentbiodata->admissionNo = $admissionno;
        $studentbiodata->tittle = '';
        $studentbiodata->firstname = $firstname;
        $studentbiodata->lastname = $surname;
        $studentbiodata->othername = $othername ?? 'Nill';
        $studentbiodata->gender = $gender;
        $studentbiodata->home_address = "$homeaddress";
        $studentbiodata->home_address2 = '';
        $studentbiodata->dateofbirth = $dob;
        $studentbiodata->age = $age;
        $studentbiodata->placeofbirth = $placeofbirth;
        $studentbiodata->religion = $religion;
        $studentbiodata->nationlity = $nationality;
        $studentbiodata->state = $state;
        $studentbiodata->local = "$local";
        $studentbiodata->last_school = $lastschool ?? 'Nill';
        $studentbiodata->last_class = $lastclass;
        $studentbiodata->registeredBy = Auth::user()->id;
        $studentbiodata->batchid = $batchid;
        $studentbiodata->statusId =  1; // Assign statusId

        $studentbiodata->save(); // ✅ Save first

        $studentId = $studentbiodata->id; // ✅ Retrieve ID after saving

        // Student Parent

        $parent->studentId = $studentId;
        $parent->father_title = $father_title;
        $parent->father = $father;
        $parent->father_phone = $father_phone;
        $parent->office_address = $office_address;
        $parent->father_occupation = $father_occupation;
        $parent->mother_title = $mother_title;
        $parent->mother = $mother;
        $parent->mother_phone = $mother_phone;
        $parent->mother_occupation = $mother_occupation;
        $parent->mother_office_address = $mother_office_address;
        $parent->parent_address = $parent_address;
        $parent->religion = $parent_religion;
        $parent->save();

        //for student picture...
        $picture->studentid = $studentId;
        $picture->save();

        //registering school class and arm for the student
        $studentclass->studentId = $studentId;
        $studentclass->schoolclassid = $schoolclassid;
        $studentclass->termid = '1';
        $studentclass->sessionid = $sessionid;
        $studentclass->save();


        //second term
        $studentclass2 = new Studentclass();
        $studentclass2->studentId = $studentId;
        $studentclass2->schoolclassid = $request->schoolclassid;
        $studentclass2->termid = '2';
        $studentclass2->sessionid = $request->sessionid;
        $studentclass2->save();

        //third term
        $studentclass3 = new Studentclass();
        $studentclass3->studentId = $studentId;
        $studentclass3->schoolclassid = $request->schoolclassid;
        $studentclass3->termid = '3';
        $studentclass3->sessionid = $request->sessionid;
        $studentclass3->save();

        //for class history...
        $promotion->studentId = $studentId;
        $promotion->schoolclassid = $schoolclassid;
        $promotion->termid = $termid;
        $promotion->sessionid = $sessionid;
        $promotion->promotionStatus = 'PROMOTED';
        $promotion->classstatus = 'CURRENT';
        $promotion->save();

        //for student house...
        $studenthouse->studentid = $studentId;
        $studenthouse->termid = $termid;
        $studenthouse->sessionid = $sessionid;
        $studenthouse->save();

        //for student personality profile...
        $studentpersonalityprofile->studentid = $studentId;
        $studentpersonalityprofile->schoolclassid = $schoolclassid;
        $studentpersonalityprofile->termid = $termid;
        $studentpersonalityprofile->sessionid = $sessionid;
        $studentpersonalityprofile->save();

        //return $this->studentsimportsheet($schoolclassid,$termid,$sessionid);

    }

    public function rules(): array
    {
        global $_sclassid;
        global $_termid;
        global $_sessionid;
        global $_batchid;

        $_sclassid = Session::get('sclassid');
        $_termid = Session::get('tid');
        $_sessionid = Session::get('sid');
        $_batchid = Session::get('batchid');

        return [

            '15' => function ($attribute, $value, $onFailure) use (&$_sclassid) {
                if ($value != $_sclassid) {
                    $onFailure('This data does not match the selected School Class');
                }
            },

            '16' => function ($attribute, $value, $onFailure) use (&$_termid) {
                if ($value != $_termid) {
                    $onFailure('This data does not match the selected School Term');
                }
            },

            '17' => function ($attribute, $value, $onFailure) use (&$_sessionid) {
                if ($value != $_sessionid) {
                    $onFailure('This data does not match the selected School Session');
                }
            },

        ];
    }

    public function customValidationMessages()
    {
        return [
            '14.in' => 'Custom message for :attribute.',
            '15.in' => 'Custom message for :attribute.',
            '16.in' => 'Custom message for :attribute.',

        ];
    }

    public function customValidationAttributes()
    {
        return [
            '14' => 'schoolclassid',
            '15' => 'termid',
            '16' => 'sessionsid',
        ];
    }

    public function startRow(): int
    {
        return 2;
    }

    public function uniqueBy()
    {
        return 'id';
    }

    public function upsertColumns()
    {
        return ['ca1', 'ca2', 'exam'];
    }

    public function onFailure(Failure $failure)
    {

    }

    // public function onError(\Throwable $e)
    // {
    //     // Handle the exception how you'd like.
    //     StudentBatchModel::where("id",Session::get('batchid'))->update(["Status" => "Failed"]);
    // }

}
