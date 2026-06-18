<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index()
    {
        return Agent::with('user')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required',
            'phone' => 'required',
            'branch_name' => 'required',
            'address' => 'required'
        ]);

        $agent = Agent::create([
            'user_id' => $request->user()->id,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'branch_name' => $data['branch_name'],
            'address' => $data['address']
        ]);

        return response()->json($agent);
    }

    public function show($id)
    {
        return Agent::with('user')->findOrFail($id);
    }
}