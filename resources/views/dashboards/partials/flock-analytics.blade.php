<div class="row">
    <!-- Flock Info Summary -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Flock Information</h6>
                <div class="row">
                    <div class="col-6">
                        <p class="mb-1"><strong>Total Birds:</strong></p>
                        <h4 class="text-primary">{{ number_format($flockAnalysis['totalBirdsAll'] ?? 0, 0) }}</h4>
                    </div>
                    <div class="col-6">
                        <p class="mb-1"><strong>Current Birds:</strong></p>
                        <h4 class="text-success">{{ number_format($flockAnalysis['currentBirdsAll'] ?? 0, 0) }}</h4>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <p class="mb-1"><strong>Total Mortality:</strong></p>
                        <h4 class="text-danger">{{ number_format($flockAnalysis['totalMortalityAll'] ?? 0, 0) }}</h4>
                        <small class="text-muted">
                            Mortality Rate: {{ $flockAnalysis['totalBirdsAll'] > 0 ? number_format(($flockAnalysis['totalMortalityAll'] / $flockAnalysis['totalBirdsAll']) * 100, 1) : 0 }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Flock Details -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Flock Details</h6>
                @foreach($flocks as $flock)
                    @if(isset($flockAnalysis['flocks'][$flock->id]))
                    @php
                        $data = $flockAnalysis['flocks'][$flock->id];
                        $mortalityRate = $data['totalBirds'] > 0 ? ($data['totalMortality'] / $data['totalBirds'] * 100) : 0;
                    @endphp
                    <div class="d-flex justify-content-between mb-2">
                        <span>Flock {{ $flock->id }}:</span>
                        <div>
                            <span class="badge bg-info">{{ $data['currentBirds'] }} birds</span>
                            <span class="badge bg-danger ms-1">{{ $data['totalMortality'] }} dead</span>
                            <small class="text-muted ms-1">({{ number_format($mortalityRate, 1) }}%)</small>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Individual Flock Performance -->
<div class="row">
    @foreach($flocks as $flock)
        @if(isset($flockAnalysis['flocks'][$flock->id]))
        @php
            $data = $flockAnalysis['flocks'][$flock->id];
            $mortalityRate = $data['totalBirds'] > 0 ? ($data['totalMortality'] / $data['totalBirds'] * 100) : 0;
        @endphp
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">Flock {{ $flock->id }}</h6>
                        <span class="badge {{ $flock->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($flock->status) }}
                        </span>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Initial</small>
                            <p class="mb-1">{{ number_format($data['totalBirds'], 0) }}</p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Current</small>
                            <p class="mb-1">{{ number_format($data['currentBirds'], 0) }}</p>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <small class="text-muted">Mortality</small>
                            <p class="mb-1 text-danger">{{ number_format($data['totalMortality'], 0) }}</p>
                            <small class="text-muted">{{ number_format($mortalityRate, 1) }}% rate</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Age: {{ $flockAges[$flock->id] ?? 0 }} weeks</small>
                        <br>
                        <small class="text-muted">First: {{ $data['firstDate'] }}</small>
                        <br>
                        <small class="text-muted">Last: {{ $data['lastDate'] }}</small>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach
</div>