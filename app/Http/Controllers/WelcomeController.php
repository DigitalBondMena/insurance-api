<?php

namespace App\Http\Controllers;

use App\User;
use App\NotificationSender;
use App\NotificationSenderClaim;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;

class WelcomeController extends Controller
{
    /**
     * فك التشفير وتهيئة Google Client من FIREBASE_CREDENTIALS_BASE64
     */
    private function initFirebaseClient()
    {
        $decoded = base64_decode(env('FIREBASE_CREDENTIALS_BASE64'));
        $tempFile = storage_path('app/firebase_credentials.json');
        file_put_contents($tempFile, $decoded);

        $client = new GoogleClient();
        $client->setAuthConfig($tempFile);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();

        @unlink($tempFile); // حذف الملف بعد الاستخدام للأمان

        return $token['access_token'];
    }

    /**
     * إرسال إشعار جماعي لكل المستخدمين
     */
    public function sendPushNotification(Request $request)
    {
        $message = $request->textmessage ?? "message";
        $title = $request->titlemessage ?? "title";
        $armessage = $request->artextmessage ?? "message";
        $artitle = $request->artitlemessage ?? "title";

        $userTokens = User::whereNotNull('device_token')->pluck('device_token')->unique()->values()->toArray();
        $tokensArray = array_chunk($userTokens, 1000);

        $accessToken = $this->initFirebaseClient();

        $header = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        foreach ($tokensArray as $chunk) {
            foreach ($chunk as $deviceToken) {
                $postdata = [
                    "message" => [
                        "token" => $deviceToken,
                        "notification" => [
                            "title" => $title,
                            "body" => $message,
                        ],
                        "apns" => [
                            "payload" => [
                                "aps" => [
                                    "alert" => [
                                        "title" => $title,
                                        "body" => $message,
                                    ],
                                    "sound" => "default",
                                ],
                            ],
                        ],
                    ],
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/capital-insurance-8134f/messages:send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
                curl_exec($ch);
                curl_close($ch);
            }
        }

        NotificationSender::create([
            'notification_title' => $title,
            'notification_text' => $message,
            'ar_notification_title' => $artitle,
            'ar_notification_text' => $armessage,
            'notification_date' => now('Africa/Cairo')->format('Y-m-d'),
            'notification_time' => now('Africa/Cairo')->format('H:i:s'),
        ]);

        return response()->json(['success' => 'Notifications sent successfully'], 200);
    }

    /**
     * إرسال إشعار لمستخدم واحد (عام)
     */
    public function sendSingleNotification(Request $request)
    {
        $message = $request->textmessage ?? "message";
        $title = $request->titlemessage ?? "title";
        $userid = $request->user_id;

        $user = User::find($userid);
        if (!$user || !$user->device_token) {
            return response()->json(['error' => 'User has no device token'], 404);
        }

        $accessToken = $this->initFirebaseClient();

        $header = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $postdata = [
            "message" => [
                "token" => $user->device_token,
                "notification" => [
                    "title" => $title,
                    "body" => $message,
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "alert" => [
                                "title" => $title,
                                "body" => $message,
                            ],
                            "sound" => "default",
                        ],
                    ],
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/capital-insurance-8134f/messages:send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_exec($ch);
        curl_close($ch);

        return response()->json(['success' => 'Single notification sent successfully']);
    }

    /**
     * إشعار خاص بالمطالبات (Claims)
     */
    public function sendSingleNotificationClaim(Request $request)
    {
        $message = $request->textmessage ?? "message";
        $title = $request->titlemessage ?? "title";
        $user = User::find($request->user_id);

        if (!$user || !$user->device_token) {
            return response()->json(['error' => 'User has no device token'], 404);
        }

        $accessToken = $this->initFirebaseClient();
        $header = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $postdata = [
            "message" => [
                "token" => $user->device_token,
                "notification" => [
                    "title" => $title,
                    "body" => $message,
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/capital-insurance-8134f/messages:send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_exec($ch);
        curl_close($ch);

        NotificationSenderClaim::create([
            'notification_title' => $title,
            'notification_text' => $message,
            'user_id' => $user->id,
            'claim_id' => $request->claim_id,
            'notification_date' => now('Africa/Cairo')->format('Y-m-d'),
            'notification_time' => now('Africa/Cairo')->format('H:i:s'),
        ]);

        return response()->json(['success' => 'Claim notification sent successfully']);
    }

    /**
     * إشعار خاص بالأوردرات (Orders)
     */
    public function sendOrderNotification(Request $request)
    {
        $message = $request->textmessage ?? "message";
        $title = $request->titlemessage ?? "title";
        $user = User::find($request->user_id);

        if (!$user || !$user->device_token) {
            return response()->json(['error' => 'User has no device token'], 404);
        }

        $accessToken = $this->initFirebaseClient();
        $header = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $postdata = [
            "message" => [
                "token" => $user->device_token,
                "notification" => [
                    "title" => $title,
                    "body" => $message,
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/capital-insurance-8134f/messages:send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_exec($ch);
        curl_close($ch);

        return response()->json(['success' => 'Order notification sent successfully']);
    }
}
