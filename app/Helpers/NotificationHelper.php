<?php

namespace App\Helpers;

use App\Events\AdminNotificationTriggered;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function new_notification(
    string  $type,
    string  $message,
    string  $url,
    ?int    $userId = null,
): Notification {
    $notification = Notification::create([
        'user_id' => $userId,
        'type'    => $type,
        'message' => $message,
        'url'     => $url,
        'is_read' => false,
    ]);

    $userName = $userId ? optional(User::find($userId))->name : null;

    try {
        logger('About to dispatch event', ['id' => $notification->id]);

        event(new AdminNotificationTriggered(
            id:        $notification->id,
            type:      $type,
            message:   $message,
            url:       $url,
            createdAt: $notification->created_at->diffForHumans(),
            userName:  $userName,
        ));

        logger('Event dispatched successfully');

        $title = match($type) {
            'low_stock'     => 'Warnung: Niedriger Bestand',
            'order_request' => 'Neue Bestellanfrage',
            default         => 'Neue Benachrichtigung',
        };

        $auth = [
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        // Fully qualified — no use statements needed
        $webPush = new \Minishlink\WebPush\WebPush($auth, [], 30, ["verify" => false]);

        User::where('role', 'admin')->each(function (User $admin) use ($webPush, $title, $message, $url) {
            $subscriptions = DB::table('push_subscriptions')
                ->where('subscribable_type', 'App\\Models\\User')
                ->where('subscribable_id', $admin->id)
                ->get();

            logger('Push subscriptions found', ['count' => $subscriptions->count(), 'admin' => $admin->id]);

            foreach ($subscriptions as $sub) {
                try {
                    $webPush->queueNotification(
                        \Minishlink\WebPush\Subscription::create([
                            'endpoint'        => $sub->endpoint,
                            'contentEncoding' => 'aes128gcm',
                            'keys' => [
                                'auth'       => $sub->auth_token,
                                'p256dh'       => $sub->public_key,
                            ],
                        ]),
                        json_encode([
                            'title'   => $title,
                            'message' => $message,
                            'url'     => $url,
                        ])
                    );
                    logger('WebPush queued for admin: ' . $admin->id);
                } catch (\Throwable $e) {
                    logger('WebPush queue failed', ['error' => $e->getMessage()]);
                }
            }
        });

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                logger('WebPush delivery failed', [
                    'reason'   => $report->getReason(),
                    'endpoint' => $report->getEndpoint(),
                ]);
            } else {
                logger('WebPush delivered successfully');
            }
        }

    } catch (\Throwable $e) {
        logger('FAILED', [
            'error' => $e->getMessage(),
            'line'  => $e->getLine(),
            'file'  => $e->getFile(),
        ]);
    }

    return $notification;
}