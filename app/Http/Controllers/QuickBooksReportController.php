<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuickBooksToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;

class QuickBooksReportController extends Controller
{
    private function getBaseUrl()
    {
        return config('qbo.sandbox') ? "https://sandbox-quickbooks.api.intuit.com" : "https://quickbooks.api.intuit.com";
    }


    public function showReportsPage()
    {
        $isConnected = QuickBooksToken::exists();
        return view('reports.select_report', compact('isConnected'));
    }


    public function fetchReport(Request $request, $reportName)
    {

        $startDate = $request->query('start_date', '2025-01-01');
        $endDate   = $request->query('end_date', '2025-12-31');

        $token = QuickBooksToken::first();
        if (!$token) {
            return response()->json(['error' => 'QuickBooks is not connected. Please connect first.'], 401);
        }

        $realmId = Crypt::decryptString($token->realm_id);
        $accessToken = $token->access_token;

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
            ])->get($url, [
                'start_date'            => $startDate,
                'end_date'              => $endDate,

            ]);
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


    public function fetchReportLive(Request $request)
    {
        $reportName = $request->input('report_name');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $token = QuickBooksToken::first();
        if (!$token) {
            return response()->json(['error' => 'QuickBooks is not connected. Please connect first.'], 401);
        }

        $realmId = Crypt::decryptString($token->realm_id);
        $accessToken = $token->access_token;

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
            'client_id' => config('qbo.client_id'),
            'client_secret' => config('qbo.client_secret'),
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


    public function fetchBalanceSheetFlattened(Request $request)
    {

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');


        $token = QuickBooksToken::first();
        if (!$token) {
            return response()->json(['error' => 'QuickBooks is not connected. Please connect first.'], 401);
        }


    $realmId = Crypt::decryptString($token->realm_id);
    $accessToken = $token->access_token;


    if (Carbon::now()->greaterThan($token->expires_at)) {
        if (!$this->refreshToken()) {
            return response()->json(['error' => 'Failed to refresh token'], 401);
        }
        $token = QuickBooksToken::first();
        $accessToken = $token->access_token;
    }

    $url = $this->getBaseUrl()."/v3/company/{$realmId}/reports/BalanceSheet";
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Accept'        => 'application/json'
    ])->get($url, [
        'start_date'            => $startDate,
        'end_date'              => $endDate,
      //  'summarize_column_by' => 'Total',
    ]);

    if ($response->failed()) {
        return response()->json([
            'error' => 'Failed to fetch report',
            'details' => $response->json()
        ], 400);
    }

    $reportJsonData = $response->json();


    $startPeriod = $reportJsonData['Header']['StartPeriod'] ?? $startDate;
    $year = substr($startPeriod, 0, 4);


    $rows = [];
    if (isset($reportJsonData['Rows'])) {
        $rows = $this->flattenRows(
            $reportJsonData['Rows'],
            [],
            'PNE Pizza LLC',
            $year
        );
    }

    $filename = "Balance_Sheet_Export.csv";
    $headers  = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];


    return response()->stream(function () use ($rows) {
        $file = fopen('php://output', 'w');


        fputcsv($file, [
            'Company Name',
            'Year',
            'Level 1',
            'Level 2',
            'Level 3',
            'Level 4',
            'Account Name',
            'Value'
        ]);


        foreach ($rows as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }, 200, $headers);
}




// Helper function to process rows similar to JavaScript
/**
 * Recursively processes the nested Rows array from QuickBooks.
 * Builds an array of rows; each row has 8 columns:
 * [CompanyName, Year, Level1, Level2, Level3, Level4, AccountName, Value].
 *
 * @param  array  $rows
 * @param  array  $hierarchy
 * @param  string $companyName
 * @param  string $year
 * @return array
 */
private function flattenRows(array $rows, array $hierarchy, string $companyName, string $year)
{
    $result = [];

    // QuickBooks might wrap rows in $rows['Row'], so we expect that:
    if (!isset($rows['Row'])) {
        return $result; // no data
    }

    foreach ($rows['Row'] as $section) {
        // Make a copy of the incoming hierarchy
        $currentHierarchy = $hierarchy;

        // 1) If there's a Header, push it onto the hierarchy
        if (isset($section['Header']['ColData'][0]['value']) && !empty($section['Header']['ColData'][0]['value'])) {
            $currentHierarchy[] = $section['Header']['ColData'][0]['value'];
        }

        // 2) Recurse if there are sub-rows
        if (isset($section['Rows'])) {
            // Merge child rows into our result
            $childFlattened = $this->flattenRows($section['Rows'], $currentHierarchy, $companyName, $year);
            $result = array_merge($result, $childFlattened);
        }

        // 3) If there's ColData, that's an actual "line item"
        if (isset($section['ColData'])) {
            // Make sure we have exactly 4 levels
            while (count($currentHierarchy) < 4) {
                $currentHierarchy[] = '';
            }

            $accountName = $section['ColData'][0]['value'] ?? '';
            $value       = $section['ColData'][1]['value'] ?? '';

            // Build one row of data
            $rowData = [
                $companyName,
                $year,
                $currentHierarchy[0],
                $currentHierarchy[1],
                $currentHierarchy[2],
                $currentHierarchy[3],
                $accountName,
                $value
            ];
            $result[] = $rowData;
        }
    }

    return $result;
}



public function fetchProfitAndLossDetail(Request $request)
{

    $startDate = $request->query('start_date');
    $endDate   = $request->query('end_date');

    $token = QuickBooksToken::first();
    if (!$token) {
        return response()->json(['error' => 'QuickBooks is not connected.'], 401);
    }
    $realmId     = Crypt::decryptString($token->realm_id);
    $accessToken = $token->access_token;



     if (Carbon::now()->greaterThan($token->expires_at)) {
        if (!$this->refreshToken()) {
            return response()->json(['error' => 'Failed to refresh token'], 401);
        }
        $token = QuickBooksToken::first();
        $accessToken = $token->access_token;
    }


    $url = $this->getBaseUrl()."/v3/company/{$realmId}/reports/ProfitAndLossDetail";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Accept'        => 'application/json'
    ])->get($url, [
        'start_date'           => $startDate,
        'end_date'             => $endDate,
        // Possibly needed: 'accounting_method' => 'Accrual' or 'Cash',
        // Or 'summarize_column_by' => 'Total'
    ]);

    if ($response->failed()) {
        return response()->json([
            'error' => 'Failed to fetch P&L Detail',
            'details' => $response->json(),
        ], 400);
    }

    $reportJsonData = $response->json();

    // 3) Identify how many columns QuickBooks gave us
    //    They appear in ['Columns']['Column'] with 'ColTitle'
    $columns     = $reportJsonData['Columns']['Column'] ?? [];
    $columnCount = count($columns);

    // 4) Build the CSV header row from QuickBooks' column titles.
    //    Example: ["Date", "Transaction Type", "Num", "Name", "Class", "Memo/Description", "Split", "Amount", "Balance"]
    $headerTitles = array_map(function ($col) {
        return $col['ColTitle'] ?? '';
    }, $columns);

    // 5) Recursively flatten *all* rows (Header, ColData, Summary, sub-rows)
    $flattened = [];
    if (isset($reportJsonData['Rows']['Row'])) {
        $this->flattenAllRows($reportJsonData['Rows']['Row'], $flattened, $columnCount);
    }

    // 6) Return CSV
    $filename = "Profit_and_Loss_AllData.csv";
    $headers  = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];

    return response()->stream(function () use ($headerTitles, $flattened) {
        $file = fopen('php://output', 'w');

        fputcsv($file, $headerTitles);


        foreach ($flattened as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }, 200, $headers);
}


/**
 * Recursively processes an array of QBO rows (["Row" => ...]),
 * extracting up to $columnCount columns from Header, ColData, and Summary.
 *
 * @param  array $rows         The array of Row objects from QuickBooks
 * @param  array &$flattened   Reference to our final "flat" array of CSV rows
 * @param  int   $columnCount  How many columns QBO says exist
 */
private function flattenAllRows(array $rows, array &$flattened, int $columnCount)
{
    foreach ($rows as $section) {

        if (isset($section['Header']['ColData'])) {
            $headerRow = $this->colDataToCsvRow($section['Header']['ColData'], $columnCount);
            $flattened[] = $headerRow;
        }


        if (isset($section['ColData'])) {
            $dataRow = $this->colDataToCsvRow($section['ColData'], $columnCount);
            $flattened[] = $dataRow;
        }


        if (isset($section['Summary']['ColData'])) {
            $summaryRow = $this->colDataToCsvRow($section['Summary']['ColData'], $columnCount);
            $flattened[] = $summaryRow;
        }

        // 4) If there are nested rows, recurse
        if (isset($section['Rows']['Row'])) {
            $this->flattenAllRows($section['Rows']['Row'], $flattened, $columnCount);
        }
    }
}

/**
 * Convert an array of QuickBooks-style ColData to exactly $columnCount columns.
 * Example:
 *    Input: [ ["value"=>"2025-01-06"], ["value"=>"Journal Entry"], ... ]
 *    Output (if $columnCount=9): ["2025-01-06", "Journal Entry", "", ..., ""]
 */
private function colDataToCsvRow(array $colData, int $columnCount): array
{
    $row = [];
    for ($i = 0; $i < $columnCount; $i++) {
        $row[] = $colData[$i]['value'] ?? '';
    }
    return $row;
}



public function fetchProfitAndLossDetailall(Request $request)
{
    Log::info("Starting fetchProfitAndLossDetailall function.");

    // 1) Verify or refresh your QuickBooks token
    $token = QuickBooksToken::first();
    if (!$token) {
        Log::error("QuickBooks token not found. Connection issue.");
        return response()->json(['error' => 'QuickBooks is not connected.'], 401);
    }
    Log::info("QuickBooks token found.");

    $realmId = Crypt::decryptString($token->realm_id);
    $accessToken = $token->access_token;

    if (Carbon::now()->greaterThan($token->expires_at)) {
        Log::warning("QuickBooks token expired. Attempting to refresh...");
        if (!$this->refreshToken()) {
            Log::error("Failed to refresh QuickBooks token.");
            return response()->json(['error' => 'Failed to refresh token'], 401);
        }
        $token = QuickBooksToken::first();
        $accessToken = $token->access_token;
        Log::info("QuickBooks token refreshed successfully.");
    }

    $urlBase = $this->getBaseUrl()."/v3/company/{$realmId}/reports/ProfitAndLossDetail";
    Log::info("Base URL for QuickBooks API: " . $urlBase);

    $chunks = [
        ['start_date' => '2023-01-01', 'end_date' => '2023-06-29'],
        ['start_date' => '2023-06-30', 'end_date' => '2023-12-31'],
        ['start_date' => '2024-01-01', 'end_date' => '2024-04-29'],
        ['start_date' => '2024-04-30', 'end_date' => '2024-08-31'],
        ['start_date' => '2024-09-01', 'end_date' => '2024-12-31'],
        ['start_date' => '2025-01-01', 'end_date' => '2025-04-29'],
        ['start_date' => '2025-04-30', 'end_date' => '2025-08-31'],
        ['start_date' => '2025-09-01', 'end_date' => '2025-12-31'],
        ['start_date' => '2026-01-01', 'end_date' => '2026-04-29'],
        ['start_date' => '2026-04-30', 'end_date' => '2026-08-31'],
        ['start_date' => '2026-09-01', 'end_date' => '2026-12-31'],
    ];

    Log::info("Processing " . count($chunks) . " chunks.");

    $allFlattenedRows = [];
    $headerTitles = [];
    $columnCount  = 0;

    foreach ($chunks as $chunk) {
        Log::info("Fetching data for chunk: " . $chunk['start_date'] . " to " . $chunk['end_date']);

        $startTime = microtime(true);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept'        => 'application/json'
        ])->get($urlBase, [
            'start_date' => $chunk['start_date'],
            'end_date'   => $chunk['end_date'],
        ]);

        if ($response->failed()) {
            Log::error("Failed to fetch P&L Detail for " . $chunk['start_date'] . " - " . $chunk['end_date'], [
                'status' => $response->status(),
                'error'  => $response->json(),
            ]);
            return response()->json([
                'error'   => 'Failed to fetch P&L Detail',
                'details' => $response->json(),
            ], 400);
        }

        $reportJsonData = $response->json();
        Log::info("Successfully fetched data for chunk: " . $chunk['start_date'] . " to " . $chunk['end_date']);

        // Grab column headers if we haven't yet
        if (!$headerTitles) {
            $columns = $reportJsonData['Columns']['Column'] ?? [];
            $columnCount = count($columns);
            $headerTitles = array_map(function ($col) {
                return $col['ColTitle'] ?? '';
            }, $columns);
            Log::info("Extracted column headers: " . implode(', ', $headerTitles));
        }

        // Flatten rows for this chunk
        if (isset($reportJsonData['Rows']['Row'])) {
            $flattened = [];
            $this->flattenAllRows($reportJsonData['Rows']['Row'], $flattened, $columnCount);

            // Remove newlines in all fields of the flattened rows
            foreach ($flattened as &$flatRow) {
                foreach ($flatRow as &$field) {
                    $field = str_replace(["\r", "\n"], " ", $field);
                }
            }

            $allFlattenedRows = array_merge($allFlattenedRows, $flattened);

            Log::info("Processed " . count($flattened) . " rows for chunk: " . $chunk['start_date'] . " to " . $chunk['end_date']);
        } else {
            Log::warning("No data found for chunk: " . $chunk['start_date'] . " to " . $chunk['end_date']);
        }

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        Log::info("Chunk " . $chunk['start_date'] . " - " . $chunk['end_date'] . " took {$executionTime} seconds to process.");
    }

    Log::info("Total flattened rows collected: " . count($allFlattenedRows));

    $filename = "Profit_and_Loss_AllData.csv";
    $headers  = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];

    Log::info("Generating CSV file with " . count($allFlattenedRows) . " rows.");

    return response()->stream(function () use ($headerTitles, $allFlattenedRows) {
        $file = fopen('php://output', 'w');

        fputcsv($file, $headerTitles);

        foreach ($allFlattenedRows as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }, 200, $headers);
}



















}
