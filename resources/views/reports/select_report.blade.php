<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('QuickBooks Reports') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if ($isConnected)
                        <!-- If connected, show Disconnect button -->
                        <h3 class="text-lg font-semibold mb-4">{{ __('Disconnect from QuickBooks') }}</h3>

                        <p class="mb-4 text-gray-700 dark:text-gray-300">
                            You are currently connected to QuickBooks. If you wish to disconnect, click the button below.
                        </p>

                        <form action="{{ route('qbo.disconnect') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="px-6 py-3 bg-red-600 dark:bg-red-500 text-white font-semibold rounded-md shadow-sm hover:bg-red-700 dark:hover:bg-red-400 transition">
                                Disconnect from QuickBooks
                            </button>
                        </form>
                    @else
                        <!-- If not connected, show Connect button -->
                        <h3 class="text-lg font-semibold mb-4">{{ __('Connect to QuickBooks') }}</h3>

                        <p class="mb-4 text-gray-700 dark:text-gray-300">
                            Click the button below to authorize QuickBooks integration.
                        </p>

                        <a href="{{ route('qbo.connect') }}"
                            class="inline-flex items-center px-6 py-3 bg-blue-600 dark:bg-blue-500 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 dark:hover:bg-blue-400 transition">
                            <img src="{{ asset('img/C2QB_white_btn.png') }}" alt="Connect to QuickBooks">
                        </a>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">{{ __('Select a QuickBooks Report') }}</h3>

                    <form id="reportForm">
                        <div class="mb-3">
                            <label for="report_name" class="block font-medium text-gray-700 dark:text-gray-300">
                                Choose a Report
                            </label>
                            <select id="report_name" name="report_name"
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-200 border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="0">Select a Report</option>
                                <option value="BalanceSheet">Balance Sheet</option>
                                <option value="ProfitAndLoss">Profit & Loss</option>
                                <option value="ProfitAndLossDetail">Profit & Loss Details</option>
                                <option value="CashFlow">Cash Flow</option>
                                <option value="TransactionList">Transaction List</option>
                                <option value="CustomerIncome">Customer Income</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="start_date" class="block font-medium text-gray-700 dark:text-gray-300">
                                    Start Date
                                </label>
                                <input type="date" id="start_date" name="start_date"
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-200 border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="end_date" class="block font-medium text-gray-700 dark:text-gray-300">
                                    End Date
                                </label>
                                <input type="date" id="end_date" name="end_date"
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-200 border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <button id="submitReport" type="submit"
                            class="mt-4 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white font-semibold rounded-md shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-400 hidden" >
                            Get Report
                        </button>
                    </form>

                    <div id="loading" class="mt-4 hidden text-gray-900 dark:text-gray-200">
                        <p>Loading report...</p>
                    </div>

                    <div id="reportResults" class="mt-6 hidden">
                        <h3 class="text-lg font-semibold">{{ __('Report Results') }}</h3>
                        <p class="text-gray-700 dark:text-gray-300"><strong>Report Name:</strong> <span
                                id="reportTitle"></span></p>
                        <p class="text-gray-700 dark:text-gray-300"><strong>Period:</strong> <span
                                id="reportPeriod"></span></p>
                        <button id="downloadCsv"
                            class="mt-2 px-4 py-2 bg-green-600 dark:bg-green-500 text-white font-semibold rounded-md shadow-sm hover:bg-green-700 dark:hover:bg-green-400">
                            Download CSV
                        </button>

                        <button id="exportToDataWarehouse"
                            class="mt-2 ml-2 px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 dark:hover:bg-blue-400 export-btn">
                            Export to DataWarehouse
                        </button>

                        <button id="exportWithCategory"
                            class="mt-2 ml-2 px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 dark:hover:bg-blue-400 export-btn">
                            ExportWithCategory
                        </button>


                        <button id="exportHierarchicalData"
                            class="mt-2 px-4 py-2 bg-purple-600 dark:bg-purple-500 text-white font-semibold rounded-md shadow-sm hover:bg-purple-700 dark:hover:bg-purple-400 export-btn">
                            Export Balance Sheet
                        </button>


                        <div class="overflow-x-auto mt-4">
                            <table id="reportTable"
                                class="min-w-full table-auto border border-gray-300 dark:border-gray-700 shadow-sm rounded-md">

                                <thead>
                                    <tr id="tableHeader"
                                        class="bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-200"></tr>
                                </thead>
                                <tbody id="reportTableBody" class="text-gray-800 dark:text-gray-200"></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>



$(document).ready(function () {
    var reportJsonData = null;

    $("#report_name").change(function () {
        if ($(this).val() != "0") {
            $("#submitReport").removeClass("hidden");
        } else {
            $("#submitReport").addClass("hidden");
        }
    });


    $("#reportForm").submit(function (event) {
        event.preventDefault();
        $("#loading").removeClass("hidden");
        $("#reportResults").addClass("hidden");

        var reportName = $("#report_name").val();
        var startDate = $("#start_date").val();
        var endDate = $("#end_date").val();



        $.ajax({
            url: "{{ route('qbo.fetch.report') }}",
            method: "POST",
            data: {
                report_name: reportName,
                start_date: startDate,
                end_date: endDate,
                _token: "{{ csrf_token() }}"
            },
            success: function (response) {


                $("#loading").addClass("hidden");

                if (response.error) {
                    alert(response.error);
                    return;
                }

                reportJsonData = response.data;
                $("#reportTitle").text(reportJsonData.Header.ReportName);
                $("#reportPeriod").text(reportJsonData.Header.StartPeriod + " to " + reportJsonData.Header.EndPeriod);

                var rowsHtml = "";
                var headerHtml = "";

                if (reportJsonData.Columns && reportJsonData.Columns.Column) {
                    reportJsonData.Columns.Column.forEach(function (col) {
                        headerHtml += `<th class="border border-gray-300 dark:border-gray-700 p-2">${col.ColTitle}</th>`;
                    });
                }

                function processRows(rows) {
                    if (!rows || !rows.Row) return;

                    rows.Row.forEach(function (section) {
                        if (section.Header && section.Header.ColData) {
                            rowsHtml += `<tr><td colspan="${section.Header.ColData.length}" class="border border-gray-300 dark:border-gray-700 p-2"><strong>${section.Header.ColData[0]?.value || ''}</strong></td></tr>`;
                        }

                        if (section.Rows) {
                            processRows(section.Rows);
                        }

                        if (section.ColData) {
                            var rowData = section.ColData.map(function (col) {
                                return col.value || "";
                            });
                            rowsHtml += `<tr>${rowData.map(function (data) {
                                return `<td class="border border-gray-300 dark:border-gray-700 p-2">${data}</td>`;
                            }).join("")}</tr>`;
                        }

                        if (section.Summary && section.Summary.ColData) {
                            var summaryData = section.Summary.ColData.map(function (col) {
                                return col.value || "";
                            });
                            rowsHtml += `<tr class="bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-200">${summaryData.map(function (data) {
                                return `<td class="border border-gray-300 dark:border-gray-700 p-2"><strong>${data}</strong></td>`;
                            }).join("")}</tr>`;
                        }
                    });
                }

                if (reportJsonData.Rows) {
                    processRows(reportJsonData.Rows);
                }

                $("#tableHeader").html(headerHtml);
                $("#reportTableBody").html(rowsHtml);
                $("#reportResults").removeClass("hidden");
                handleExportButtons(reportName);
            },
            error: function () {
                $("#loading").addClass("hidden");
                alert("Failed to fetch the report. Please try again.");
            }
        });
    });

    // ✅ Function to show export buttons AFTER fetching the report
function handleExportButtons(selectedReport) {
    let visibilityMap = {
        "BalanceSheet": ["#exportHierarchicalData"],
        "ProfitAndLoss": [],
        "ProfitAndLossDetail": ["#exportToDataWarehouse", "#exportWithCategory"],
        "CashFlow": [],
        "TransactionList": ["#exportToDataWarehouse"],
        "CustomerIncome": []
    };

    // Hide all export buttons first
    $(".export-btn").addClass("hidden");

    // Show only the relevant buttons
    if (visibilityMap[selectedReport]) {
        visibilityMap[selectedReport].forEach(selector => {
            $(selector).removeClass("hidden");
        });
    }
}

    // ✅ Fixed CSV Download Function
    $("#downloadCsv").click(function () {
        var csv = [];
        var rows = $("#reportTable tr");

        rows.each(function () {
            var rowData = [];
            $(this).find("td, th").each(function () {
                var cellText = $(this).text().trim();

                rowData.push('"' + cellText.replace(/"/g, '""') + '"');
            });
            csv.push(rowData.join(","));
        });

        if (csv.length === 0) {
            alert("No data available for download.");
            return;
        }

        var csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        var downloadLink = document.createElement("a");
        downloadLink.download = reportJsonData.Header.ReportName + ".csv";
        downloadLink.href = URL.createObjectURL(csvFile);
        downloadLink.click();
    });






$("#exportHierarchicalData").click(function () {
if (!reportJsonData || !reportJsonData.Rows) {
    alert("No valid report data available.");
    return;
}

var csv = [];
var reportYear = reportJsonData.Header.StartPeriod.substring(0, 4); // Extract year

function processRows(rows, hierarchy) {
    if (!rows || !rows.Row) return;

    rows.Row.forEach(function (section) {
        let currentHierarchy = [...hierarchy];

        // ✅ Handle headers (categories and subcategories)
        if (section.Header && section.Header.ColData && section.Header.ColData[0].value) {
            currentHierarchy.push(section.Header.ColData[0].value);
        }

        // ✅ Process nested rows (subcategories)
        if (section.Rows) {
            processRows(section.Rows, currentHierarchy);
        }

        // ✅ Process actual account data
        if (section.ColData) {
            var rowData = ['"PNE Pizza LLC"', '"' + reportYear + '"'];

            // Ensure exactly 4 hierarchy levels
            while (currentHierarchy.length < 4) {
                currentHierarchy.push(""); // Fill empty levels
            }

            // ✅ Fix: Ensure Account Name is always in the correct column
            var accountName = section.ColData[0].value.replace(/"/g, '""');
            var value = section.ColData[1].value.replace(/"/g, '""');

            rowData.push(...currentHierarchy.map(col => `"${col}"`));
            rowData.push(`"${accountName}"`, `"${value}"`);

            csv.push(rowData.join(","));
        }
    });
}

// ✅ Start processing the JSON hierarchy
processRows(reportJsonData.Rows, []);

if (csv.length === 0) {
    alert("No valid data available for export.");
    return;
}

// ✅ Create CSV headers
var headers = ['Company Name', 'Year', 'Level 1', 'Level 2', 'Level 3', 'Level 4', 'Account Name', 'Value'];
csv.unshift(headers.join(",")); // Add headers to CSV

// ✅ Create & download CSV file
var csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
var downloadLink = document.createElement("a");
downloadLink.download = "Balance_Sheet_Export.csv";
downloadLink.href = URL.createObjectURL(csvFile);
downloadLink.click();
});





    $("#exportToDataWarehouse").click(function () {
        var csv = [];
        var rows = $("#reportTable tr");

        rows.each(function () {
            var rowData = [];
            var firstCell = $(this).find("td, th").first().text().trim();


            if (!/^\d{4}-\d{2}-\d{2}$/.test(firstCell)) {
                return;
            }

            $(this).find("td, th").each(function () {
                var cellText = $(this).text().trim();

                rowData.push('"' + cellText.replace(/"/g, '""') + '"');
            });

            csv.push(rowData.join(","));
        });

        if (csv.length === 0) {
            alert("No valid data available for export.");
            return;
        }

        var csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        var downloadLink = document.createElement("a");
        downloadLink.download = "DataWarehouse_Export.csv";
        downloadLink.href = URL.createObjectURL(csvFile);
        downloadLink.click();
    });



    $("#exportWithCategory").click(function () {
        var csv = [];
        var rows = $("#reportTable tr");
        var currentCategory = "";

        rows.each(function () {
            var rowData = [];
            var firstCell = $(this).find("td, th").first().text().trim();


            if (!/^\d{4}-\d{2}-\d{2}$/.test(firstCell) && firstCell.length > 0) {

                currentCategory = firstCell.replace(/^\d+\s*/, "").trim();
                return;
            }


            if (/^\d{4}-\d{2}-\d{2}$/.test(firstCell)) {
                $(this).find("td, th").each(function () {
                    var cellText = $(this).text().trim();
                    rowData.push('"' + cellText.replace(/"/g, '""') + '"');
                });

                rowData.push('"' + currentCategory.replace(/"/g, '""') + '"');
                csv.push(rowData.join(","));
            }
        });

        if (csv.length === 0) {
            alert("No valid data available for export.");
            return;
        }

        var csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        var downloadLink = document.createElement("a");
        downloadLink.download = "Data_With_Category.csv";
        downloadLink.href = URL.createObjectURL(csvFile);
        downloadLink.click();
    });
});




    </script>

</x-app-layout>
