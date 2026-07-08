<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint'                         => 'required|url',
            'keys.auth'                        => 'required|string',
            'keys.p256dh'                      => 'required|string',
        ]);

        $request->user()->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth'],
        );

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $request->user()->deletePushSubscription($request->endpoint);
        return response()->json(['success' => true]);
    }
}