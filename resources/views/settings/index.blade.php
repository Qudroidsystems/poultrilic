@extends('layouts.master')
@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Settings</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Finance</a></li>
                                <li class="breadcrumb-item active">Settings</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Prices and costs</h5></div>
                        <div class="card-body">
                            <p class="text-muted">These drive the revenue, cost and profit figures on the dashboard and in exports.</p>
                            <form method="POST" action="{{ route('settings.update') }}">
                                @csrf
                                @method('PUT')

                                @foreach ($fields as $key => $meta)
                                    <div class="row mb-3 align-items-center">
                                        <label for="{{ $key }}" class="col-sm-4 col-form-label">{{ $meta['label'] }}</label>
                                        <div class="col-sm-5">
                                            <input type="number" step="0.01" min="0" class="form-control @error($key) is-invalid @enderror"
                                                   id="{{ $key }}" name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" required>
                                            @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-sm-3 text-muted small">{{ $meta['unit'] }}</div>
                                    </div>
                                @endforeach

                                <hr>
                                <div class="form-check form-switch mb-1">
                                    <input type="hidden" name="use_recorded_expenses" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="use_recorded_expenses"
                                           name="use_recorded_expenses" value="1" @checked(old('use_recorded_expenses', $settings['use_recorded_expenses']))>
                                    <label class="form-check-label" for="use_recorded_expenses">Use recorded expenses on the dashboard</label>
                                </div>
                                <div class="form-text mb-4">
                                    Off: dashboard costs are estimated from feed bags, drug days and labor days using the rates above.
                                    On: dashboard costs are the expenses you record on the Expenses page.
                                </div>

                                @can('Update settings')
                                    <button type="submit" class="btn btn-primary">Save settings</button>
                                @endcan
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
