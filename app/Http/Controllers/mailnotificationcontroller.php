<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Notifications\TestEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class mailnotificationcontroller extends Controller
{
   public function sendnotification()
   {    
        $users = User::get();

        $enrollmentData =[
            'body'=>'You Recived an new test notification',
            'enrollmentText'=>'You are allowed to enroll',
            'url'=>url('/'),
            'thankyou'=>'You have 14 days to enroll'
        ];
        foreach ($users as $user) {
            $user->notify(new TestEnrollment($enrollmentData));
        }
        // Notification::send($users, new TestEnrollment($enrollmentData));
   }
}
