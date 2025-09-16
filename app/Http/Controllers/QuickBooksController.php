<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use QuickBooksOnline\API\DataService\DataService;
use App\Models\QuickBooksToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class QuickBooksController extends Controller
{
    private function getDataService()
    {
        return DataService::Configure([
            'auth_mode'     => 'oauth2',
            'ClientID'      => config('qbo.client_id'),
            'ClientSecret'  => config('qbo.client_secret'),
            'RedirectURI'   => config('qbo.redirect_uri'),
            'scope'         => config('qbo.scope'),
            'baseUrl'       => config('qbo.base_url'),
        ]);
    }
    // Redirect to QuickBooks Authentication
    public function connect()
    {
        $dataService = $this->getDataService();
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
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
        
        $dataService = $this->getDataService();

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

        try {
            // Exchange authorization code for an access token
            $accessTokenObj = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($code, $realmId);

            // Encrypt refresh token and realm ID for security
            $encryptedRealmId = Crypt::encryptString((string) $realmId);
            $encryptedRefreshToken = Crypt::encryptString((string) $accessTokenObj->getRefreshToken());

            // Store tokens in the database
            QuickBooksToken::updateOrCreate(
                ['realm_id' => $encryptedRealmId],
                [
                    'access_token'  => $accessTokenObj->getAccessToken(),
                    'refresh_token' => $encryptedRefreshToken,
                    'expires_at'    => now()->addMinutes(60)
                ]
            );

            // Ensure the route exists before redirecting
            if (\Route::has('qbo.reports')) {
                return redirect()->route('qbo.reports')->with('success', 'QuickBooks Connected!');
            } else {
                return redirect('/')->with('success', 'QuickBooks Connected!');
            }

        } catch (\Exception $e) {
            \Log::error('QuickBooks Token Exchange Failed: ' . $e->getMessage());

            return response()->json([
                'error'   => 'Failed to exchange token',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Disconnect from QuickBooks
    public function disconnect()
    {
        // Remove QuickBooks Token from Database
        QuickBooksToken::truncate();

        return redirect()->route('qbo.reports')->with('success', 'You have successfully disconnected QuickBooks.');
    }
}
