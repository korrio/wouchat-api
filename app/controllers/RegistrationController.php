<?php

use Illuminate\Support\Facades\Input;

class RegistrationController extends \BaseController {

    public function store()
    {
        $rules = [
            'username' => 'required|min:6|unique:accounts',
            'email' => 'required|email|unique:accounts',
            'password' => 'required|confirmed|min:6'
        ];

        $input = Input::only(
            'username',
            'email',
            'password',
            'password_confirmation'
        );

        $validator = Validator::make($input, $rules);

        if($validator->fails())
        {
            return Response::json($validator->messages());
            //return Redirect::back()->withInput()->withErrors($validator);
        }

        $confirmation_code = str_random(30);

        $user = Account::create([
            'username' => Input::get('username'),
            'email' => Input::get('email'),
            'password' => Hash::make(Input::get('password')),
            'email_verification_key' => $confirmation_code
        ]);

        Mail::send('email.verify', $confirmation_code, function($message) {
            $message->to(Input::get('email'), Input::get('username'))
                ->subject('Verify your email address');
        });

        //Flash::message('Thanks for signing up! Please check your email.');

        return Response::json($user);
    }
}