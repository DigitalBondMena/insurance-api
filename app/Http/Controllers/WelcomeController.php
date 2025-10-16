<?php

namespace App\Http\Controllers;

use App\About;
use App\HomeSlider;
use App\HomeCounter;
use App\HomeAbout;
use App\HomeService;
use App\HomeInvest;
use App\Client;
use App\Blog;
use App\BlogSubCategory;
use App\BlogCategory;
use App\AboutPage;
use App\AboutUs;
use App\Contact;
use App\AdmissionForm;
use App\Newsletter;
use App\ProtocolScope;
use App\SubCategory;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\NotificationSender;
use App\NotificationSenderClaim;
use Google\Client as GoogleClient;
use App\User;

class WelcomeController extends Controller
{

   public function sendPushNotification(Request $request)
    {
        $message = $request->textmessage ?? "message";
        $title = $request->titlemessage ?? "title";
        $armessage = $request->artextmessage ?? "message";
        $artitle = $request->artitlemessage ?? "title";
    
        // Get all unique device tokens from the database
        $userTokens = User::whereNotNull('device_token')->pluck('device_token')->toArray();
        $uniqueArray = array_unique($userTokens);
        $tokensArray = array_chunk($uniqueArray, 1000); // Break tokens into chunks of 1000 for batch processing
    
        // Path to the Firebase service account credentials JSON file
        $credentialsFilePath = "capital-insurance-8134f-a0ba5c65d52f.json";
    
        // Initialize the Google Client
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();
    
        $accessToken = $token['access_token'];
    
        // Set the request headers
        $header = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];
    
        // Send notifications
        foreach ($tokensArray as $chunk) {
            foreach ($chunk as $deviceToken) {
                $postdata = [
                    "message" => [
                        "token" => $deviceToken,
                        "notification" => [
                            "title" => $title,
                            "body" => $message,
                        ],
                        // APNs payload for iOS
                        "apns" => [
                            "payload" => [
                                "aps" => [
                                    "alert" => [
                                        "title" => $title,
                                        "body" => $message
                                    ],
                                    "sound" => "default"
                                ]
                            ]
                        ]
                    ]
                ];
    
                $dataString = json_encode($postdata);
    
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/capital-insurance-8134f/messages:send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    
                $result = curl_exec($ch);
    
                if ($result === false) {
                    $errorMsg = curl_error($ch);
                    curl_close($ch);
                    continue; // Log and skip the failed request
                }
    
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
    
                if ($httpCode !== 200) {
                    // Log::error("FCM API Error: " . $result);
                }
            }
        }
    
        $dateNow = Carbon::now('Africa/Cairo')->format('Y-m-d');
        $timeNow = Carbon::now('Africa/Cairo')->format('h:i:s');
    
        $notification = NotificationSender::create([
            'notification_title' => $title,
            'notification_text' => $message,
            'ar_notification_title' => $artitle,
            'ar_notification_text' => $armessage,
            'notification_date' => $dateNow,
            'notification_time' => $timeNow,
        ]);
    
        return response()->json(['success' => 'Notifications sent successfully'], 200);
    }

    
    public function sendSingleNotification(Request $request)
    {
        $message = $request->textmessage ?? "message";
        $title = $request->titlemessage ?? "title";
        $armessage = $request->artextmessage ?? "message";
        $artitle = $request->artitlemessage ?? "title";
        $userid = $request->user_id;
        $requestid = $request->request_id;
        $requesttype = $request->request_type;
    
        $userTokens = User::where('id', $userid)->whereNotNull('device_token')->pluck('device_token')->toArray();
        $userdata = User::where('id', $userid)->first();
        $uniqueArray = array_unique($userTokens);
        $tokensArray = array_chunk($uniqueArray, 1000);
    
        $credentialsFilePath = "capital-insurance-8134f-a0ba5c65d52f.json";
    
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();
        $accessToken = $token['access_token'];
    
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
                                        "body" => $message
                                    ],
                                    "sound" => "default"
                                ]
                            ]
                        ]
                    ]
                ];
    
                $dataString = json_encode($postdata);
    
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/capital-insurance-8134f/messages:send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    
                $result = curl_exec($ch);
                if ($result === false) {
                    curl_close($ch);
                    continue;
                }
    
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            }
        }
    
        $dateNow = Carbon::now('Africa/Cairo')->format('Y-m-d');
        $timeNow = Carbon::now('Africa/Cairo')->format('h:i:s');
    
        NotificationSender::create([
            'user_id' => $userdata->id,
            'notification_title' => $title,
            'notification_text' => $message,
            'ar_notification_title' => $artitle,
            'ar_notification_text' => $armessage,
            'notification_date' => $dateNow,
            'notification_time' => $timeNow,
            'request_id' => $requestid,
            'request_type' => $requesttype,
        ]);
    
        return response()->json(['success' => 'Notification sent successfully'], 200);
    }
    

    public function sendSingleNotificationClaim(Request $request)
    {
        $message = $request->textmessage ?? "message";
        $title = $request->titlemessage ?? "title";
        $armessage = $request->artextmessage ?? "message";
        $artitle = $request->artitlemessage ?? "title";
        $userid = $request->user_id;
        $requestid = $request->request_id;
        $requesttype = $request->request_type;
    
        $userTokens = User::where('id', $userid)->whereNotNull('device_token')->pluck('device_token')->toArray();
        $userdata = User::where('id', $userid)->first();
        $uniqueArray = array_unique($userTokens);
        $tokensArray = array_chunk($uniqueArray, 1000);
    
        $credentialsFilePath = "capital-insurance-8134f-a0ba5c65d52f.json";
    
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();
        $accessToken = $token['access_token'];
    
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
                                        "body" => $message
                                    ],
                                    "sound" => "default"
                                ]
                            ]
                        ]
                    ]
                ];
    
                $dataString = json_encode($postdata);
    
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/capital-insurance-8134f/messages:send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    
                $result = curl_exec($ch);
                if ($result === false) {
                    curl_close($ch);
                    continue;
                }
    
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            }
        }
    
        $dateNow = Carbon::now('Africa/Cairo')->format('Y-m-d');
        $timeNow = Carbon::now('Africa/Cairo')->format('h:i:s');
    
        NotificationSenderClaim::create([
            'user_id' => $userdata->id,
            'notification_title' => $title,
            'notification_text' => $message,
            'ar_notification_title' => $artitle,
            'ar_notification_text' => $armessage,
            'notification_date' => $dateNow,
            'notification_time' => $timeNow,
            'request_id' => $requestid,
            'request_type' => $requesttype,
        ]);
    
        return response()->json(['success' => 'Notification sent successfully'], 200);
    }

    
    
    
    public function sendOrderNotification(Request $request) {
        $message = $request->textmessage ?? "message";
        $title = $request->titlemessage ?? "title";
        $armessage = $request->artextmessage ?? "message";
        $artitle = $request->artitlemessage ?? "title";
        $userid = $request->user_id;
    
        // Get all unique device tokens from the database
        $userTokens = User::where('id' , $userid)->whereNotNull('device_token')->pluck('device_token')->toArray();
        $userdata = User::where('id' , $userid)->get()->first();
        $uniqueArray = array_unique($userTokens);
        $tokensArray = array_chunk($uniqueArray, 1000); // Break tokens into chunks of 1000 for batch processing
    
        // Path to the Firebase service account credentials JSON file
        $credentialsFilePath = "json/capital-insurance-8134f-a0ba5c65d52f.json";
    
        // Initialize the Google Client
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();
    
        // Get the access token
        $accessToken = $token['access_token'];
    
        // Set the request headers
        $header = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];
    
        // Prepare the notification content
        $notification = [
            "title" => $title,
            "body" => $message,
        ];
    
        // Send notifications
        foreach ($tokensArray as $chunk) {
            foreach ($chunk as $deviceToken) {
                // Prepare the payload for each token
                $postdata = [
                    "message" => [
                        "token" => $deviceToken,
                        "notification" => $notification,
                    ],
                ];
    
                $dataString = json_encode($postdata);
    
                // Initialize cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/capital-insurance-8134f/messages:send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    
                // Execute the request and handle response
                $result = curl_exec($ch);
    
                // Error handling
                if ($result === false) {
                    $errorMsg = curl_error($ch);
                    curl_close($ch);
                    continue; // Log and skip the failed request
                }
    
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
    
                // Log any non-200 response for debugging
                if ($httpCode !== 200) {
                    // Log or handle the error based on $result
                    // Example: Log::error("FCM API Error: " . $result);
                }
            }
        }
                
    
       return response()->json(['success' => 'helloworld'], 200); 
    }

}
