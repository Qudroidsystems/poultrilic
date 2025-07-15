<!DOCTYPE html>
<html>
<head>
    <title>Poultry Analytics Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { margin-bottom: 20px; }
        .header p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Poultry Analytics Report</h1>
        <p><strong>Date Range:</strong> {{ $startDate->format('Y-m-d') }} to {{ $endDate->format('Y-m-d') }}</p>
        <p><strong>Flock:</strong> {{ $flockId ? 'Flock ' . $flockId : 'All Flocks' }}</p>
    </div>
    <table>
        <thead>
            <tr>
                @foreach ($data[0] ?? [] as $key => $value)
                    <th>{{ $key }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
