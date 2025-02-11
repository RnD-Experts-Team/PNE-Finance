<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use QuickBooksOnline\API\DataService\DataService;
use App\Models\QuickBooksToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class QuickBooksController extends Controller
{
    // Redirect to QuickBooks Authentication
    public function connect()
    {
        $dataService = DataService::Configure([
            'auth_mode'       => 'oauth2',
            'ClientID'        => env('QBO_CLIENT_ID'),
            'ClientSecret'    => env('QBO_CLIENT_SECRET'),
            'RedirectURI'     => env('QBO_REDIRECT_URI'),
            'scope'           => 'com.intuit.quickbooks.accounting',
            'baseUrl'         => env('QBO_SANDBOX') ? "sandbox" : "production"
        ]);

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

        // Ensure the authorization URL is a string
        $authUrl = (string) $OAuth2LoginHelper->getAuthorizationCodeURL();

        return redirect($authUrl);
    }

    // Handle QuickBooks OAuth Callback
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $realmId = $request->query('realmId');

        // Ensure the values are strings and not arrays
        $code = is_array($code) ? reset($code) : $code;
        $realmId = is_array($realmId) ? reset($realmId) : $realmId;

        // Validate parameters
        if (!$code || !$realmId) {
            return response()->json(['error' => 'Missing authorization parameters from QuickBooks'], 400);
        }

        $dataService = DataService::Configure([
            'auth_mode'       => 'oauth2',
            'ClientID'        => env('QBO_CLIENT_ID'),
            'ClientSecret'    => env('QBO_CLIENT_SECRET'),
            'RedirectURI'     => env('QBO_REDIRECT_URI'),
            'baseUrl'         => env('QBO_SANDBOX') ? "sandbox" : "production"
        ]);

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

        try {
            // Exchange authorization code for an access token
            $accessTokenObj = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($code, $realmId);

            // QuickBooks tokens expire in 60 minutes
            $expiresAt = now()->addMinutes(60);

            // Store tokens in the database
            QuickBooksToken::updateOrCreate(
                ['realm_id' => $realmId],
                [
                    'access_token'  => $accessTokenObj->getAccessToken(),
                    'refresh_token' => $accessTokenObj->getRefreshToken(),
                    'expires_at'    => $expiresAt
                ]
            );

            return redirect()->route('qbo.reports')->with('success', 'QuickBooks Connected!');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to exchange token', 'message' => $e->getMessage()], 400);
        }
    }



    public function disconnect()
{
    // Remove QuickBooks Token from Database
    QuickBooksToken::truncate();

    return view('qbo.disconnected')->with('message', 'You have successfully disconnected QuickBooks.');
}

}
