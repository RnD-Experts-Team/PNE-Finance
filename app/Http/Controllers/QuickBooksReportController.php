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
    // 1) Verify or refresh your QuickBooks token
    $token = QuickBooksToken::first();
    if (!$token) {
        return response()->json(['error' => 'QuickBooks is not connected.'], 401);
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

    $urlBase = $this->getBaseUrl()."/v3/company/{$realmId}/reports/ProfitAndLossDetail";

    // 2) Define your date ranges.
    //    If you specifically want (Jan 1 - Jun 30, Jun 30 - Dec 31) for each year from 2023 - 2026,
    //    here they are explicitly. You can, of course, generate them programmatically.
    $chunks = [
        ['start_date' => '2023-01-01', 'end_date' => '2023-06-30'],
        ['start_date' => '2023-06-30', 'end_date' => '2023-12-31'],

        ['start_date' => '2024-01-01', 'end_date' => '2024-06-30'],
        ['start_date' => '2024-06-30', 'end_date' => '2024-12-31'],

        ['start_date' => '2025-01-01', 'end_date' => '2025-06-30'],
        ['start_date' => '2025-06-30', 'end_date' => '2025-12-31'],

        ['start_date' => '2026-01-01', 'end_date' => '2026-06-30'],
        ['start_date' => '2026-06-30', 'end_date' => '2026-12-31'],
    ];

    // 3) We’ll accumulate all "flattened" rows from each chunk in here:
    $allFlattenedRows = [];

    // 4) Columns / header row – we’ll pick them up from the first successful response
    $headerTitles = [];
    $columnCount  = 0;

    // 5) Loop over each chunk, make the same request, flatten rows, accumulate
    foreach ($chunks as $chunk) {

        // Make the request for this 6-month window
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept'        => 'application/json'
        ])->get($urlBase, [
            'start_date' => $chunk['start_date'],
            'end_date'   => $chunk['end_date'],
            // optional parameters:
            // 'accounting_method' => 'Accrual' or 'Cash',
            // 'summarize_column_by' => 'Total'
        ]);

        if ($response->failed()) {
            // We can either fail early or continue to the next chunk;
            // here we'll fail early and return an error
            return response()->json([
                'error'   => 'Failed to fetch P&L Detail',
                'details' => $response->json(),
            ], 400);
        }

        $reportJsonData = $response->json();

        // On the very first successful chunk, extract the columns/titles
        if (!$headerTitles) {
            $columns = $reportJsonData['Columns']['Column'] ?? [];
            $columnCount = count($columns);
            // Build the CSV header row from QuickBooks' column titles
            // e.g.: ["Date", "Transaction Type", "Num", "Name", "Class", "Memo/Description", "Split", "Amount", "Balance"]
            $headerTitles = array_map(function ($col) {
                return $col['ColTitle'] ?? '';
            }, $columns);
        }

        // Flatten the rows for this chunk
        if (isset($reportJsonData['Rows']['Row'])) {
            // We assume you have a helper method flattenAllRows that
            // recursively flattens out the 'Row' and pushes them into an array
            $flattened = [];
            $this->flattenAllRows($reportJsonData['Rows']['Row'], $flattened, $columnCount);

            // Merge into our main array
            $allFlattenedRows = array_merge($allFlattenedRows, $flattened);
        }
    }

    // 6) Return all rows in a single CSV
    $filename = "Profit_and_Loss_AllData.csv";
    $headers  = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];

    return response()->stream(function () use ($headerTitles, $allFlattenedRows) {
        $file = fopen('php://output', 'w');

        // CSV header
        fputcsv($file, $headerTitles);

        // CSV data
        foreach ($allFlattenedRows as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }, 200, $headers);
}

















}
