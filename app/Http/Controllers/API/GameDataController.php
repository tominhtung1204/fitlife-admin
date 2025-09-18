<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AssignDiet;
use App\Models\Diet;
use App\Models\Workout;
use App\Models\AssignWorkout;
use App\Http\Resources\DietResource;
use App\Http\Resources\GameScoreDataResource;
use App\Http\Resources\WorkoutResource;
use App\Models\GameScoreData;

class GameDataController extends Controller
{
    public function getScore(Request $request)
    {
        $scores = GameScoreData::query();

        $per_page = config('constant.PER_PAGE_LIMIT', 10);
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page == -1) {
                $per_page = $scores->count();
            }
        }

        $scores = $scores->orderBy('score', 'desc')->paginate($per_page);

        $items = GameScoreDataResource::collection($scores);

        $response = [
            'pagination' => json_pagination_response($scores),
            'data'       => $items,
        ];

        return json_custom_response($response);
    }

    public function saveScore(Request $request)
    {
        $auth_user = auth()->user();
        $user_id = $auth_user->id;

        GameScoreData::where('user_id', $user_id)->delete();

        GameScoreData::create([
            'user_id'      => $user_id,
            'score'        => $request->score,
            'country_code' => $request->country_code,
        ]);

        return json_message_response(__('message.save_form', ['form' => __('message.game_data')]));
    }
  
}
