<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pagetitle }}</title>
    <style>
        :root {
            --primary: #2563eb;
            --success: #059669;
            --danger: #dc2626;
            --warning: #d97706;
            --info: #0891b2;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --bg-light: #f3f4f6;
            --border: #e5e7eb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: var(--text-primary);
            background-color: #f9fafb;
            margin: 15px;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            padding: 0.8rem;
            margin: 0 auto;
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 0.8rem;
            padding: 0.8rem;
            border-bottom: 2px solid var(--border);
        }
        .header h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.3rem;
        }
        .header h2 {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin: 0.5rem 0;
        }
        .header p {
            color: var(--text-secondary);
            margin: 0.1rem 0;
            font-size: 10px;
        }
        .meta-info {
            background: var(--bg-light);
            border-radius: 4px;
            padding: 0.5rem;
            margin: 0.5rem 0;
            font-size: 10px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .meta-info p {
            margin: 0.2rem 1rem;
        }
        .flock-info {
            margin: 0.5rem 0;
            background: #fff;
            border-radius: 4px;
            padding: 0.8rem;
            border: 1px solid var(--border);
        }
        .flock-info table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .flock-info td {
            padding: 0.3rem 0.5rem;
            vertical-align: top;
            font-size: 11px;
        }
        .flock-info td:first-child {
            font-weight: 600;
            color: var(--text-primary);
            width: 160px;
        }
        .summary-card {
            margin: 0.8rem 0;
            padding: 0.8rem;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 6px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: stretch;
            gap: 1rem;
        }
        .summary-mini {
            min-width: 100px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--bg-light);
            border-radius: 4px;
            border: 1px solid var(--border);
            padding: 0.5rem 0.3rem;
            box-sizing: border-box;
        }
        .summary-mini .row {
            display: flex;
            align-items: baseline;
            gap: 0.15rem;
            margin-bottom: 0.2rem;
        }
        .summary-mini .amount {
            font-size: 16px;
            font-weight: 700;
        }
        .summary-mini .label {
            font-size: 9px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            text-align: center;
        }
        .text-success { color: var(--success); }
        .text-danger { color: var(--danger); }
        .text-warning { color: var(--warning); }
        .text-info { color: var(--info); }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0.8rem 0 0.5rem 0;
            padding-bottom: 0.3rem;
            border-bottom: 1px solid var(--border);
        }
        .analytics-table-container {
            margin: 0.5rem 0;
            overflow-x: auto;
        }
        .analytics-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 9px;
            border: 1px solid var(--border);
        }
        .analytics-table th,
        .analytics-table td {
            border: 1px solid var(--border);
            padding: 0.4rem 0.3rem;
            text-align: left;
            word-wrap: break-word;
        }
        .analytics-table th {
            background: var(--bg-light);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 9px;
        }
        .analytics-table td {
            color: var(--text-secondary);
            font-size: 9px;
        }
        .analytics-table tr:nth-child(even) {
            background-color: #f7fafc;
        }
        .status-badge {
            padding: 0.1rem 0.4rem;
            border-radius: 2px;
            font-size: 8px;
            font-weight: 600;
            display: inline-block;
        }
        .status-good {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-average {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-poor {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--text-secondary);
            font-size: 9px;
        }
        .timestamp {
            margin-top: 0.3rem;
            font-style: italic;
            color: var(--text-secondary);
        }
        .generated-by {
            margin-top: 0.2rem;
            font-size: 9px;
        }
        @media print {
            body { background: none; margin: 0; padding: 0; }
            .container { max-width: none; margin: 0; padding: 0.3rem; }
            .analytics-table,
            .analytics-table th,
            .analytics-table td {
                border: 1px solid #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .analytics-table th {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .analytics-table tr:nth-child(even) {
                background-color: #f7fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .summary-card, .flock-info { border: 1px solid #000 !important; }
            .summary-mini { 
                border: 1px solid #000 !important; 
                background: #eee !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .status-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        .currency {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Farm Information -->
        <div class="header">
            <h1>Poultry Farm Analytics Report</h1>
            <p>PrimeFarm Poultry Management System</p>
            <p>Email: analytics@primefarm.ng | Phone: +234 XXX XXX XXXX</p>
            <h2>Poultry Performance Analytics</h2>
            <div class="meta-info">
                <p>Report Number: {{ $statementNumber }}</p>
                <p>Period: {{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}</p>
                <p>Generated: {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}</p>
            </div>
        </div>

        <!-- Flock Information -->
        @if ($studentdata->isNotEmpty())
            @foreach ($studentdata as $flock)
                <div class="flock-info">
                    <table>
                        <tr>
                            <td>Flock ID:</td>
                            <td>{{ $flock->admissionNo }}</td>
                        </tr>
                        <tr>
                            <td>Flock Name:</td>
                            <td>{{ $flock->firstname }}</td>
                        </tr>
                        <tr>
                            <td>Farm Location:</td>
                            <td>{{ $flock->homeadd }}</td>
                        </tr>
                        <tr>
                            <td>Analysis Period:</td>
                            <td>{{ $schoolsession }}</td>
                        </tr>
                        <tr>
                            <td>Report Type:</td>
                            <td>Weekly Performance Analytics</td>
                        </tr>
                    </table>
                </div>
            @endforeach
        @else
            <p>No flock information available.</p>
        @endif

        <!-- Summary Metrics -->
        <div class="summary-card">
            <div class="summary-mini">
                <div class="row">
                    <div class="amount">{{ number_format($summaryMetrics['totalBirds'], 0) }}</div>
                </div>
                <div class="label">Total Birds</div>
            </div>
            <div class="summary-mini">
                <div class="row">
                    <div class="amount text-info">{{ number_format($summaryMetrics['currentBirds'], 0) }}</div>
                </div>
                <div class="label">Current Birds</div>
            </div>
            <div class="summary-mini">
                <div class="row">
                    <div class="amount text-danger">{{ number_format($summaryMetrics['totalMortality'], 0) }}</div>
                </div>
                <div class="label">Total Mortality</div>
            </div>
            <div class="summary-mini">
                <div class="row">
                    <div class="amount text-success">{{ number_format($summaryMetrics['totalEggProduction'], 0) }}</div>
                </div>
                <div class="label">Eggs Produced</div>
            </div>
            <div class="summary-mini">
                <div class="row">
                    <div class="amount text-success currency">₦{{ number_format($summaryMetrics['totalRevenue'], 2) }}</div>
                </div>
                <div class="label">Total Revenue</div>
            </div>
            <div class="summary-mini">
                <div class="row">
                    <div class="amount">{{ number_format($summaryMetrics['avgProductionRate'], 1) }}%</div>
                </div>
                <div class="label">Production Rate</div>
            </div>
        </div>

        <!-- Analytics Records -->
        <h3 class="section-title">Weekly Performance Analytics</h3>
        @if ($studentpaymentbill->isNotEmpty())
            <div class="analytics-table-container">
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>Week</th>
                            <th>Birds</th>
                            <th>Eggs Produced</th>
                            <th>Eggs Sold</th>
                            <th>Feed (Bags)</th>
                            <th>Drug Usage</th>
                            <th>Production Rate</th>
                            <th>Revenue</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($studentpaymentbill as $analytics)
                            <tr>
                                <td>{{ $analytics->title }}</td>
                                <td>{{ number_format($summaryMetrics['currentBirds'], 0) }}</td>
                                <td>{{ number_format($analytics->amount, 0) }}</td>
                                <td>{{ number_format($analytics->amount_paid, 0) }}</td>
                                <td>{{ number_format($summaryMetrics['totalFeedBags'], 1) }}</td>
                                <td>{{ number_format($summaryMetrics['totalDrugUsage'], 0) }} days</td>
                                <td>{{ number_format($summaryMetrics['avgProductionRate'], 1) }}%</td>
                                <td class="currency">₦{{ number_format($summaryMetrics['totalRevenue'], 2) }}</td>
                                <td>
                                    <span class="status-badge status-{{ strtolower($analytics->payment_status) }}">
                                        {{ $analytics->payment_status }}
                                    </span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($analytics->payment_date)->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2"><strong>Totals/Averages:</strong></td>
                            <td><strong>{{ number_format($summaryMetrics['totalEggProduction'], 0) }}</strong></td>
                            <td><strong>{{ number_format($summaryMetrics['totalEggsSold'], 0) }}</strong></td>
                            <td><strong>{{ number_format($summaryMetrics['totalFeedBags'], 1) }}</strong></td>
                            <td><strong>{{ number_format($summaryMetrics['totalDrugUsage'], 0) }} days</strong></td>
                            <td><strong>{{ number_format($summaryMetrics['avgProductionRate'], 1) }}%</strong></td>
                            <td class="currency"><strong>₦{{ number_format($summaryMetrics['totalRevenue'], 2) }}</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <p>No analytics data found for the selected period.</p>
        @endif

        <!-- Performance Summary -->
        <div class="section-title">Performance Summary</div>
        <div class="flock-info">
            <table>
                <tr>
                    <td>Net Income:</td>
                    <td class="{{ $summaryMetrics['netIncome'] >= 0 ? 'text-success' : 'text-danger' }}">
                        <strong class="currency">₦{{ number_format($summaryMetrics['netIncome'], 2) }}</strong>
                        @if($summaryMetrics['netIncome'] >= 0)
                            (Profitable)
                        @else
                            (Loss)
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Bird Mortality Rate:</td>
                    <td>
                        @if($summaryMetrics['totalBirds'] > 0)
                            {{ number_format(($summaryMetrics['totalMortality'] / $summaryMetrics['totalBirds']) * 100, 1) }}%
                        @else
                            0%
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Feed Efficiency:</td>
                    <td>
                        @if($summaryMetrics['totalEggProduction'] > 0)
                            {{ number_format($summaryMetrics['totalFeedBags'] / $summaryMetrics['totalEggProduction'], 4) }} bags/egg
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Egg Sales Efficiency:</td>
                    <td>
                        @if($summaryMetrics['totalEggProduction'] > 0)
                            {{ number_format(($summaryMetrics['totalEggsSold'] / $summaryMetrics['totalEggProduction']) * 100, 1) }}%
                        @else
                            0%
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Egg Production Rate:</td>
                    <td>{{ number_format($summaryMetrics['avgProductionRate'], 1) }}%</td>
                </tr>
                <tr>
                    <td>Feed Consumption:</td>
                    <td>{{ number_format($summaryMetrics['totalFeedBags'], 1) }} bags</td>
                </tr>
                <tr>
                    <td>Drug Usage Days:</td>
                    <td>{{ number_format($summaryMetrics['totalDrugUsage'], 0) }} days</td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="timestamp">
                Generated on: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}
            </div>
            <div class="generated-by">
                Generated by: PrimeFarm Poultry Analytics System
            </div>
            <p>This is an official poultry analytics report from PrimeFarm Management System.</p>
            <p>For any queries, please contact the farm administration.</p>
            <p>Currency: Nigerian Naira (₦)</p>
        </div>
    </div>
</body>
</html>