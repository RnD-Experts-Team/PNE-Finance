<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuickBooksToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuickBooksReportController extends Controller
{
    private function getBaseUrl()
    {
        return env('QBO_SANDBOX') ? "https://sandbox-quickbooks.api.intuit.com" : "https://quickbooks.api.intuit.com";
    }

    // Show the report selection page
    public function showReportsPage()
    {
        $isConnected = QuickBooksToken::exists();
        return view('reports.select_report', compact('isConnected'));
    }

    // Fetch QuickBooks report and return JSON (Auto-refresh token)
    public function fetchReport(Request $request, $reportName)
    {
        $token = QuickBooksToken::first();
        if (!$token) {
            return response()->json(['error' => 'QuickBooks is not connected. Please connect first.'], 401);
        }

        // Decrypt stored credentials
        $realmId = Crypt::decryptString($token->realm_id);
        $accessToken = $token->access_token;

        // Check if token is expired and refresh if needed
        if (Carbon::now()->greaterThan($token->expires_at)) {
            if (!$this->refreshToken()) {
                return response()->json(['error' => 'Failed to refresh token'], 401);
            }
            $token = QuickBooksToken::first();
            $accessToken = $token->access_token;
        }

        $url = $this->getBaseUrl() . "/v3/company/{$realmId}/reports/{$reportName}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json'
        ])->get($url);

        $intuitTid = $response->header('intuit_tid');

        if ($response->failed()) {
            return response()->json([
                'error' => 'Failed to fetch report',
                'details' => $response->json(),
                'intuit_tid' => $intuitTid
            ], 400);
        }

        return response()->json([
            'message' => 'Report fetched successfully',
            'data' => $response->json(),
            'intuit_tid' => $intuitTid
        ]);
    }

    // Fetch the report live for the Blade page
    public function fetchReportLive(Request $request)
    {
        $reportName = $request->input('report_name');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $token = QuickBooksToken::first();
        if (!$token) {
            return response()->json(['error' => 'QuickBooks is not connected. Please connect first.'], 401);
        }

        // Decrypt stored credentials
        $realmId = Crypt::decryptString($token->realm_id);
        $accessToken = $token->access_token;

        // Check if token is expired and refresh if needed
        if (Carbon::now()->greaterThan($token->expires_at)) {
            if (!$this->refreshToken()) {
                return response()->json(['error' => 'Failed to refresh token'], 401);
            }
            $token = QuickBooksToken::first();
            $accessToken = $token->access_token;
        }

        $url = $this->getBaseUrl() . "/v3/company/{$realmId}/reports/{$reportName}";

        $queryParams = [];
        if ($startDate) $queryParams['start_date'] = $startDate;
        if ($endDate) $queryParams['end_date'] = $endDate;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json'
        ])->get($url, $queryParams);

        $intuitTid = $response->header('intuit_tid');

        if ($response->failed()) {
            return response()->json([
                'error' => 'Failed to fetch report',
                'details' => $response->json(),
                'intuit_tid' => $intuitTid
            ], 400);
        }

        return response()->json([
            'message' => 'Report fetched successfully',
            'data' => $response->json(),
            'intuit_tid' => $intuitTid
        ]);
    }

    // Refresh QuickBooks Access Token with encryption
    private function refreshToken()
    {
        $token = QuickBooksToken::first();
        if (!$token || !$token->refresh_token) {
            return false;
        }

        $decryptedRefreshToken = Crypt::decryptString($token->refresh_token);

        $response = Http::asForm()->post('https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $decryptedRefreshToken,
            'client_id' => env('QBO_CLIENT_ID'),
            'client_secret' => env('QBO_CLIENT_SECRET'),
        ]);

        if ($response->failed()) {
            return false;
        }

        $newToken = $response->json();

        $encryptedRefreshToken = Crypt::encryptString($newToken['refresh_token']);

        $token->update([
            'access_token' => $newToken['access_token'],
            'refresh_token' => $encryptedRefreshToken,
            'expires_at' => Carbon::now()->addSeconds($newToken['expires_in']),
        ]);

        return true;
    }
}
