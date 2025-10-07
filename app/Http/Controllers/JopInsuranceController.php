<?php

namespace App\Http\Controllers;

use App\JopInsurance;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class JopInsuranceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rows = JopInsurance::with('category' ,'jopchoices')->latest()->get();
        return response()->json([
            'success' => true,
            'message' => 'Jop Insurances Fetched Successfully',
            'data' => $rows
        ], 200); 
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.client.create');  
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'en_title' => 'string|max:255',
            'ar_title' => 'string|max:255',
            'year_money' => 'numeric',
            'month_money' => 'numeric',
            'company_name' => 'string|max:255',
            'active_status' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $requestArray = $request->all();

        $jopInsurance = JopInsurance::create($requestArray);

        return response()->json([
            'success' => true,
            'message' => 'Jop Insurance Added Successfully',
            'data' => $jopInsurance
        ], 200);
    }        
     
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $row = JopInsurance::with('category' ,'jopchoices')->findorfail($id);

        return response()->json([
            'success' => true,
            'message' => 'Jop Insurance Fetched Successfully',
            'data' => $row
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $row = JopInsurance::with('category' ,'jopchoices')->findorFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Jop Insurance Fetched Successfully',
            'data' => $row
        ], 200); 
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $jopInsurance = JopInsurance::findorFail($id);
        
        $requestArray = $request->all();

        $jopInsurance->update($requestArray);

        return response()->json([
            'success' => true,
            'message' => 'Jop Insurance Updaded Successfully',
            'data' => $jopInsurance
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        $jopInsurance = JopInsurance::findorFail($id);
        $jopInsurance->update(['active_status' => '0']);

        
        return response()->json([
            'success' => true,
            'message' => 'Jop Insurance Deleted Successfully',
            'data' => $jopInsurance
        ], 200);

    }

    public function recover($id)
    {
        $jopInsurance = JopInsurance::findorFail($id);
        $jopInsurance->update(['active_status' => '1']);

        return response()->json([
            'success' => true,
            'message' => 'Jop Insurance Recovered Successfully',
            'data' => $jopInsurance
        ], 200);
    }
    
}
