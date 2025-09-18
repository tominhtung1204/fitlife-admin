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
use App\Http\Resources\WorkoutResource;

class AssignUserController extends Controller
{
    public function getAssignDiet(Request $request)
    {
        $assign_diet = Diet::myAssignDiet();
        
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $assign_diet->count();
            }
        }

        $assign_diet = $assign_diet->orderBy('id', 'desc')->paginate($per_page);

        $items = DietResource::collection($assign_diet);

        $response = [
            'pagination'    => json_pagination_response($items),
            'data'          => $items,
        ];
        
        return json_custom_response($response);
    }

    public function getAssignWorkout(Request $request)
    {
        $assign_workout = Workout::myAssignWorkout();
        
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $assign_workout->count();
            }
        }

        $assign_workout = $assign_workout->orderBy('id', 'desc')->paginate($per_page);

        $items = WorkoutResource::collection($assign_workout);

        $response = [
            'pagination'    => json_pagination_response($items),
            'data'          => $items,
        ];
        
        return json_custom_response($response);
    } 
      
}
