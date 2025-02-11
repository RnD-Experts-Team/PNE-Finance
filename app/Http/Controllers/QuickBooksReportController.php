<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuickBooksToken;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuickBooksReportController extends Controller
{
    // Show the report selection page
    public function showReportsPage()
    {
        // Check if a QuickBooks token exists
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

        // Check if token is expired and refresh if needed
        if (Carbon::now()->greaterThan($token->expires_at)) {
            if (!$this->refreshToken()) {
                return response()->json(['error' => 'Failed to refresh token'], 401);
            }
            $token = QuickBooksToken::first(); // Reload updated token
        }

        $url = "https://sandbox-quickbooks.api.intuit.com/v3/company/{$token->realm_id}/reports/{$reportName}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token->access_token,
            'Accept' => 'application/json'
        ])->get($url);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch report', 'details' => $response->json()], 400);
        }

        return response()->json([
            'message' => 'Report fetched successfully',
            'data' => $response->json()
        ]);
    }



    public function exportCsv(Request $request)
    {
        $reportData = json_decode($request->input('report_data'), true);

        if (!$reportData) {
            return response()->json(['error' => 'No report data found.'], 400);
        }

        // Log the decoded report data for debugging purposes
        \Log::info("Decoded report data:", $reportData);

        // Set a default report name or get the one from the data
        $reportName = $reportData['Header']['ReportName'] ?? 'Report';

        $response = new StreamedResponse(function () use ($reportData) {
            $handle = fopen('php://output', 'w');

            // Add CSV Header (columns)
            $header = [];
            foreach ($reportData['Columns']['Column'] as $column) {
                $header[] = $column['ColTitle']; // Column Titles
            }
            fputcsv($handle, $header);

            // Recursive function to handle rows and sub-rows
            function processRows($rows, &$handle) {
                foreach ($rows as $row) {
                    // Check if ColData exists
                    if (isset($row['ColData'])) {
                        $rowData = [];
                        foreach ($row['ColData'] as $col) {
                            $rowData[] = $col['value'] ?? ''; // Safely access 'value' if it exists
                        }
                        fputcsv($handle, $rowData);
                    }

                    // Handle nested rows (recursive processing)
                    if (isset($row['Rows'])) {
                        processRows($row['Rows'], $handle);
                    }

                    // Handle section summaries (e.g. for 'Operating Activities' etc.)
                    if (isset($row['Summary']) && isset($row['Summary']['ColData'])) {
                        $summaryData = [];
                        foreach ($row['Summary']['ColData'] as $summaryCol) {
                            $summaryData[] = $summaryCol['value'] ?? ''; // Safely access summary data
                        }
                        fputcsv($handle, $summaryData);
                    }
                }
            }

            // Process the main rows recursively
            if (isset($reportData['Rows']['Row'])) {
                processRows($reportData['Rows']['Row'], $handle);
            }

            fclose($handle);
        });

        // Set headers for CSV download
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $reportName . '.csv"');

        return $response;
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

        // Check if token is expired and refresh if needed
        if (Carbon::now()->greaterThan($token->expires_at)) {
            if (!$this->refreshToken()) {
                return response()->json(['error' => 'Failed to refresh token'], 401);
            }
            $token = QuickBooksToken::first(); // Reload updated token
        }

        $url = "https://sandbox-quickbooks.api.intuit.com/v3/company/{$token->realm_id}/reports/{$reportName}";

        // Append date filters if provided
        $queryParams = [];
        if ($startDate) $queryParams['start_date'] = $startDate;
        if ($endDate) $queryParams['end_date'] = $endDate;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token->access_token,
            'Accept' => 'application/json'
        ])->get($url, $queryParams);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch report', 'details' => $response->json()], 400);
        }

        return response()->json([
            'message' => 'Report fetched successfully',
            'data' => $response->json()
        ]);
    }


    // Refresh QuickBooks Access Token
    private function refreshToken()
    {
        $token = QuickBooksToken::first();

        if (!$token || !$token->refresh_token) {
            return false;
        }

        $response = Http::asForm()->post('https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
            'client_id' => env('QBO_CLIENT_ID'),
            'client_secret' => env('QBO_CLIENT_SECRET'),
        ]);

        if ($response->failed()) {
            return false;
        }

        $newToken = $response->json();

        $token->update([
            'access_token' => $newToken['access_token'],
            'refresh_token' => $newToken['refresh_token'] ?? $token->refresh_token, // Only update if provided
            'expires_at' => Carbon::now()->addSeconds($newToken['expires_in']),
        ]);

        return true;
    }
}
