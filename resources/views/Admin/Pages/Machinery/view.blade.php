@extends('Admin.Particals.app')

@section('title', 'Machinery Details')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('Admin.Layouts.Sidebar')

            <div class="layout-page">
                @include('Admin.Layouts.Navbar')

                <div class="content-wrapper">
                    <div class="mx-4 flex-grow-1 container-p-y">
                        <div class="card p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="mb-0">Machinery Details</h4>
                                <a href="{{ route('admin.machinery') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-arrow-left me-1"></i>Back to List
                                </a>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Basic Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Name:</label>
                                                        <p class="mb-0">{{ $machinery->name }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Category:</label>
                                                        <p class="mb-0">{{ $machinery->category ? $machinery->category->category_name : 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Year:</label>
                                                        <p class="mb-0">{{ $machinery->year }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Weight:</label>
                                                        <p class="mb-0">{{ $machinery->weight }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Working Hours:</label>
                                                        <p class="mb-0">{{ $machinery->working_hours }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Condition:</label>
                                                        <p class="mb-0">{{ $machinery->condition }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Fuel:</label>
                                                        <p class="mb-0">{{ $machinery->fuel }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Status:</label>
                                                        <p class="mb-0">{!! $machinery->status_badge !!}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Pricing Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Buy Now Price:</label>
                                                        <p class="mb-0">${{ number_format($machinery->buy_now_price, 2) }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Bid Start Price:</label>
                                                        <p class="mb-0">${{ number_format($machinery->bid_start_price, 2) }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Bid End Time:</label>
                                                        <p class="mb-0">{{ \Carbon\Carbon::parse($machinery->bid_end_time)->format('F d, Y h:i A') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Images</h5>
                                        </div>
                                        <div class="card-body">
                                            @if($machinery->images && $machinery->images->count() > 0)
                                                <div class="row">
                                                    @foreach($machinery->images as $image)
                                                        <div class="col-md-3 col-sm-6 mb-3">
                                                            <img src="{{ asset('machinery/' . ltrim($image->image_path, '/')) }}" alt="Machinery Image" class="img-fluid rounded" style="width: 100%; height: 200px; object-fit: cover;">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-muted">No images available.</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Description</h5>
                                        </div>
                                        <div class="card-body">
                                            @if($machinery->description)
                                                <div class="border rounded p-3">
                                                    {!! $machinery->description !!}
                                                </div>
                                            @else
                                                <p class="text-muted">No description available.</p>
                                            @endif
                                        </div>
                                    </div>

                                    @if($machinery->specification)
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Specifications</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            @foreach($machinery->specification as $key => $value)
                                                                <tr>
                                                                    <td class="fw-bold">{{ $key }}</td>
                                                                    <td>{{ $value }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($machinery->offer && is_array($machinery->offer))
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Offers</h5>
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-group">
                                                    @foreach($machinery->offer as $offer)
                                                        <li class="list-group-item">{{ $offer }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Timestamps</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Created At:</label>
                                                        <p class="mb-0">{{ $machinery->created_date }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Updated At:</label>
                                                        <p class="mb-0">{{ $machinery->updated_date }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('Admin.Layouts.Footer')
                    <div class="content-backdrop fade"></div>
                </div>
            </div>

            <div class="layout-overlay layout-menu-toggle"></div>
            <div class="drag-target"></div>
        </div>
    </div>
@endsection