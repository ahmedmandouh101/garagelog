<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRecord;
use Illuminate\Http\Request;

class ServiceReceiptController extends Controller
{
    public function show(Request $request, ServiceRecord $serviceRecord)
    {
        $serviceRecord->load('car', 'garage', 'mechanic', 'parts');
        $user = $request->user();
        $owner = $serviceRecord->car->user_id === $user->id;
        $staff = $user->role === 'mechanic' && $user->garage_id === $serviceRecord->garage_id;
        abort_unless($owner || $staff, 403);

        return response()->json(['html' => view('receipts.service', ['record' => $serviceRecord])->render()]);
    }
}
