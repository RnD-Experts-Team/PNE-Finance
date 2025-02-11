@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-3">{{ $reportData['Header']['ReportName'] }}</h2>
    <p><strong>Period:</strong> {{ $reportData['Header']['StartPeriod'] }} to {{ $reportData['Header']['EndPeriod'] }}</p>
    <p><strong>Currency:</strong> {{ $reportData['Header']['Currency'] }}</p>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Account</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData['Rows']['Row'] as $section)
                <tr>
                    <td colspan="2"><strong>{{ $section['Header']['ColData'][0]['value'] ?? '' }}</strong></td>
                </tr>

                @if (isset($section['Rows']['Row']))
                    @foreach ($section['Rows']['Row'] as $category)
                        <tr>
                            <td colspan="2"><strong>{{ $category['Header']['ColData'][0]['value'] ?? '' }}</strong></td>
                        </tr>

                        @if (isset($category['Rows']['Row']))
                            @foreach ($category['Rows']['Row'] as $group)
                                <tr>
                                    <td colspan="2"><strong>{{ $group['Header']['ColData'][0]['value'] ?? '' }}</strong></td>
                                </tr>

                                @if (isset($group['Rows']['Row']))
                                    @foreach ($group['Rows']['Row'] as $account)
                                        @if (isset($account['ColData']))
                                            <tr>
                                                <td>{{ $account['ColData'][0]['value'] }}</td>
                                                <td>${{ number_format((float) $account['ColData'][1]['value'], 2) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (isset($group['Summary']['ColData']))
                                    <tr class="bg-light">
                                        <td><strong>{{ $group['Summary']['ColData'][0]['value'] }}</strong></td>
                                        <td><strong>${{ number_format((float) $group['Summary']['ColData'][1]['value'], 2) }}</strong></td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif

                        @if (isset($category['Summary']['ColData']))
                            <tr class="bg-light">
                                <td><strong>{{ $category['Summary']['ColData'][0]['value'] }}</strong></td>
                                <td><strong>${{ number_format((float) $category['Summary']['ColData'][1]['value'], 2) }}</strong></td>
                            </tr>
                        @endif
                    @endforeach
                @endif

                @if (isset($section['Summary']['ColData']))
                    <tr class="table-primary">
                        <td><strong>{{ $section['Summary']['ColData'][0]['value'] }}</strong></td>
                        <td><strong>${{ number_format((float) $section['Summary']['ColData'][1]['value'], 2) }}</strong></td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
@endsection
