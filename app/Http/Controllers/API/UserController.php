<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Traits\SubscriptionTrait;
use App\Http\Resources\UserDetailResource;

class UserController extends Controller
{
    use SubscriptionTrait;
    public function register(UserRequest $request)
    {
        $input = $request->all();
        
        $password = $input['password'];
        $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'user';
        $input['password'] = Hash::make($password);

        $input['status'] = isset($input['status']) ? $input['status']: 'pending';
        if( request('player_id') == "nil"){
            $input['player_id'] = NULL;
        }
        $input['display_name'] = $input['first_name']." ".$input['last_name'];
        $user = User::create($input);
        $user->assignRole($input['user_type']);

        if( $request->has('user_profile') && $request->user_profile != null ) {
            $user->userProfile()->create($request->user_profile);
        }

        $user->api_token = $user->createToken('auth_token')->plainTextToken;
        $user->profile_image = getSingleMedia($user, 'profile_image', null);
        unset($user->roles);
        
        $message = __('message.save_form',['form' => __('message.'.$input['user_type']) ]);
        $response = [
            'message' => $message,
            'data' => $user
        ];

        return json_custom_response($response);
    }

    public function login(Request $request)
    {      
        if(Auth::attempt(['email' => request('email'), 'password' => request('password'), 'user_type' => request('user_type')])){
            
            $user = Auth::user();

            if( $user->status == 'banned' ) {
                $message = __('message.account_banned');
                return json_message_response($message,400);
            }

            if(request('player_id') != null && request('player_id') != "nil"){
                $user->player_id = request('player_id');
            }
            $user->save();
            // $user->tokens('auth_token')->delete();
            $success = $user;
            $success['api_token'] = $user->createToken('auth_token')->plainTextToken;
            $success['profile_image'] = getSingleMedia($user, 'profile_image', null);
            
            unset($success['media']);

            return json_custom_response([ 'data' => $success ], 200 );
        } else{
            $message = __('auth.failed');
            
            return json_message_response($message,400);
        }
    }

    public function userDetail(Request $request)
    {
        $id = $request->id;

        $user = User::where('id',$id)->where('user_type', 'user')->first();
        
        if(empty($user)) {
            $message = __('message.not_found_entry', ['name' => __('message.user') ]);
            return json_message_response($message,400);   
        }

        $user_detail = new UserDetailResource($user);
        $response = [
            'data' => $user_detail,
            'subscription_detail' => $this->subscriptionPlanDetail($user->id),
        ];
        if( $user->player_id == "nil" ) {
            $user->player_id = NULL;
            $user->save();
        }
        return json_custom_response($response);

    }

    public function changePassword(Request $request)
    {
        $user = User::where('id',auth()->id())->first();

        if($user == "") {
            $message = __('message.not_found_entry', ['name' => __('message.user') ]);
            return json_message_response($message,400);   
        }
           
        $hashedPassword = $user->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        $same_exits = Hash::check($request->new_password, $hashedPassword);
        if ($match)
        {
            if($same_exits){
                $message = __('message.old_new_pass_same');
                return json_message_response($message,400);
            }

			$user->fill([
                'password' => Hash::make($request->new_password)
            ])->save();
            
            $message = __('message.password_change');
            return json_message_response($message,200);
        }
        else
        {
            $message = __('message.valid_password');
            return json_message_response($message,400);
        }
    }

    public function updateProfile(UserRequest $request)
    {   
        $user = auth()->user();

        if($request->has('id') && !empty($request->id)){
            $user = User::where('id',$request->id)->first();
        }
        if($user == null){
            return json_message_response(__('message.no_record_found'),400);
        }
        
        $user->fill($request->all())->update();

        if(isset($request->profile_image) && $request->profile_image != null ) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        $user_data = User::find($user->id);
        
        if($user_data->userProfile != null && $request->has('user_profile') ) {
            $user_data->userProfile->fill($request->user_profile)->update();
        } else if( $request->has('user_profile') && $request->user_profile != null ) {
            $user_data->userProfile()->create($request->user_profile);
        }

        $message = __('message.updated');
        
        unset($user_data['media']);

        $user_resource = new UserDetailResource($user_data);

        $response = [
            'data'      => $user_resource,
            'message'   => $message
        ];
        return json_custom_response( $response );
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if($request->is('api*'))
        {
            $user->player_id = null;
            $user->save();
            $user->currentAccessToken()->delete();
            return json_message_response('Logout successfully');
        }
    }

    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $response = Password::sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? response()->json(['message' => __($response), 'status' => true], 200)
            : response()->json(['message' => __($response), 'status' => false], 400);
    }
    
    public function socialMailLogin(Request $request)
    {
        $input = $request->all();
        
        $user_data = User::where('email',$input['email'])->first();
        
        if( $user_data != null ) {
            if( !in_array($user_data->user_type, ['admin',request('user_type')] )) {
                $message = __('auth.failed');
                return json_message_response($message,400);
            }

            if( $user_data->status == 'banned' ) {
                $message = __('message.account_banned');
                return json_message_response($message,400);
            }
        
            if( !isset($user_data->login_type) || $user_data->login_type  == '' ) {
                if( in_array($request->login_type, ['google', 'apple'] ))
                {
                    $message = __('validation.unique',['attribute' => 'email' ]);
                    return json_message_response($message,400);
                }
            }
            $message = __('message.login_success');
        }
        else
        {            
            $validator = Validator::make($input,[
                'email' => 'required|email|unique:users,email',
                'username'  => 'required|unique:users,username',
                'phone_number' => 'nullable|max:20|unique:users,phone_number',
            ]);

            if ( $validator->fails() ) {
                $data = [
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'all_message' =>  $validator->errors()
                ];
    
                return json_custom_response($data, 422);
            }

            $password = !empty($input['accessToken']) ? $input['accessToken'] : $input['email'];

            $input['display_name'] = $input['first_name']." ".$input['last_name'];
            $input['password'] = Hash::make($password);
            $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'user';
            if( request('player_id') == "nil"){
                $input['player_id'] = NULL;
            }
            $user = User::create($input);
            
            $user->assignRole($input['user_type']);

            $user_data = User::where('id',$user->id)->first();
            $message = __('message.save_form',['form' => $input['user_type'] ]);
        }
        
        // $user_data->tokens('auth_token')->delete();
        $user_data['api_token'] = $user_data->createToken('auth_token')->plainTextToken;
        $user_data['profile_image'] = getSingleMedia($user_data, 'profile_image', null);

        $response = [
            'status'    => true,
            'message'   => $message,
            'data'      => $user_data
        ];
        return json_custom_response($response);
    }

    public function socialOTPLogin(Request $request)
    {
        $input = $request->all();
        
        $user_data = User::where('username', $input['username'])->where('login_type','mobile')->first();
                
        if( $user_data != null )
        {
            if( !in_array($user_data->user_type, ['admin',request('user_type')] )) {
                $message = __('auth.failed');
                return json_message_response($message,400);
            }

            if( $user_data->status == 'banned' ) {
                $message = __('message.account_banned');
                return json_message_response($message,400);
            }
        
            if( !isset($user_data->login_type) || $user_data->login_type  == '' )
            {
                $message = __('validation.unique',['attribute' => 'username' ]);
                return json_message_response($message,400);
            }
            $message = __('message.login_success');
        }
        else
        {
            if($request->login_type === 'mobile' && $user_data == null ){
                $otp_response = [
                    'status' => true,
                    'is_user_exist' => false
                ];
                return json_custom_response($otp_response);
            }
            
            $validator = Validator::make($input,[
                'email' => 'required|email|unique:users,email',
                'username'  => 'required|unique:users,username',
                'phone_number' => 'max:20|unique:users,phone_number',
            ]);

            if ( $validator->fails() ) {
                $data = [
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'all_message' =>  $validator->errors()
                ];
    
                return json_custom_response($data, 422);
            }

            $password = !empty($input['accessToken']) ? $input['accessToken'] : $input['email'];
            if( request('player_id') == "nil"){
                $input['player_id'] = NULL;
            }
            $input['display_name'] = $input['first_name']." ".$input['last_name'];
            $input['password'] = Hash::make($password);
            $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'user';
            $user = User::create($input);

            $user->assignRole($input['user_type']);

            $user_data = User::where('id',$user->id)->first();
            $message = __('message.save_form',['form' => $input['user_type'] ]);
        }
        // $user_data->tokens('auth_token')->delete();
        $user_data['api_token'] = $user_data->createToken('auth_token')->plainTextToken;
        $user_data['profile_image'] = getSingleMedia($user_data, 'profile_image', null);

        $response = [
            'status'    => true,
            'message'   => $message,
            'data'      => $user_data
        ];
        return json_custom_response($response);
    }

    public function updateUserStatus(Request $request)
    {
        $user_id = $request->id ?? auth()->user()->id;
        
        $user = User::where('id',$user_id)->first();

        if($user == "") {
            $message = __('message.not_found_entry', ['name' => __('message.user') ]);
            return json_message_response($message,400);
        }
        if($request->has('status')) {
            $user->status = $request->status;
        }
        
        $user->save();

        
        $user_resource = new UserResource($user);
        
        $message = __('message.update_form',['form' => __('message.status') ]);
        $response = [
            'data'      => $user_resource,
            'message'   => $message
        ];
        return json_custom_response($response);
    }

    public function getAppSetting(Request $request)
    {
        if($request->has('id') && isset($request->id)){
            $data = AppSetting::where('id',$request->id)->first();
        } else {
            $data = AppSetting::first();
        }

        $app_version = [
            'android_force_update'  => SettingData('APPVERSION', 'APPVERSION_ANDROID_FORCE_UPDATE'),
            'android_version_code'  => SettingData('APPVERSION', 'APPVERSION_ANDROID_VERSION_CODE'),
            'playstore_url'         => SettingData('APPVERSION', 'APPVERSION_PLAYSTORE_URL'),
            'ios_force_update'      => SettingData('APPVERSION', 'APPVERSION_IOS_FORCE_UPDATE'),
            'ios_version'           => SettingData('APPVERSION', 'APPVERSION_IOS_VERSION'),
            'appstore_url'          => SettingData('APPVERSION', 'APPVERSION_APPSTORE_URL'),
           
        ];
        $crisp_chat =[
            'crisp_chat_website_id' => SettingData('CRISP_CHAT_CONFIGURATION', 'CRISP_CHAT_CONFIGURATION_WEBSITE_ID') ?? null,
            'is_crisp_chat_enabled' => SettingData('CRISP_CHAT_CONFIGURATION', 'CRISP_CHAT_CONFIGURATION_ENABLE/DISABLE') ? true : false,
        ];

        $mobile_game =[
            'mobile_game_enabled' => SettingData('MOBILE_GAME_ENABLE', 'MOBILE_GAME_ENABLE_TYPE') ? true : false,
        ];

        $data['app_version'] = $app_version;
        $data['crisp_chat'] = $crisp_chat;
        $data['mobile_game'] = $mobile_game;

        return json_custom_response($data);
    }

    public function deleteUserAccount(Request $request)
    {
        $id = auth()->id();
        $user = User::where('id', $id)->first();
        $message = __('message.not_found_entry',['name' => __('message.account') ]);

        if( $user != '' ) {
            $user->delete();
            $message = __('message.account_deleted');
        }
        
        return json_custom_response(['message'=> $message, 'status' => true]);
    }

    public function userProfileDetail(Request $request)
    {
        $user = auth()->user();

        $user = User::where('id',$user->id)->where('user_type', 'user')->first();
        
        if(empty($user)) {
            $message = __('message.not_found_entry', ['name' => __('message.user') ]);
            return json_message_response($message,400);   
        }

        $user_detail = new UserDetailResource($user);
        $response = [
            'data' => $user_detail,
            'subscription_detail' => $this->subscriptionPlanDetail($user->id),
        ];
        
        return json_custom_response($response);
    }
}
