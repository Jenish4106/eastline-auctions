@extends('Admin.Particals.app')

@section('title', 'Add Machinery')

@section('content')
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        @include('Admin.Layouts.Sidebar')

        <div class="layout-page">
            @include('Admin.Layouts.Navbar')

            <div class="content-wrapper">
                <div class="mx-4 flex-grow-1 container-p-y">
                    <div class="d-flex mb-3 justify-content-between align-items-center">
                        <h4>Add New Machinery</h4>
                        <a href="{{ route('admin.machinery') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i>Back to Machinery List
                        </a>
                    </div>

                    <form id="machineryForm" enctype="multipart/form-data">
                        @csrf
                        <!-- First Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="text-danger error-text category_id_error"></span>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="make" class="form-label">Make <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="make" name="make" placeholder="Enter make">
                                        <span class="text-danger error-text make_error"></span>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="model" name="model" placeholder="Enter model">
                                        <span class="text-danger error-text model_error"></span>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="offer_text" class="form-label">Offer Text</label>
                                        <input type="text" class="form-control" id="offer_text" name="offer_text" placeholder="Enter offer text">
                                        <span class="text-danger error-text offer_text_error"></span>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="image" class="form-label">Image Upload</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <span class="text-danger error-text image_error"></span>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="video" class="form-label">Video Upload</label>
                                        <input type="file" class="form-control" id="video" name="video" accept="video/*">
                                        <span class="text-danger error-text video_error"></span>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <div id="editor" style="height: 200px;"></div>
                                        <input type="hidden" id="description" name="description">
                                        <span class="text-danger error-text description_error"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Second Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="year" class="form-label">Year</label>
                                        <input type="number" class="form-control" id="year" name="year" placeholder="Enter year">
                                        <span class="text-danger error-text year_error"></span>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="weight" class="form-label">Weight (kg)</label>
                                        <input type="number" class="form-control" id="weight" name="weight" placeholder="Enter weight">
                                        <span class="text-danger error-text weight_error"></span>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="working_hours" class="form-label">Working Hours</label>
                                        <input type="number" class="form-control" id="working_hours" name="working_hours" placeholder="Enter working hours">
                                        <span class="text-danger error-text working_hours_error"></span>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="fuel_type" class="form-label">Fuel Type</label>
                                        <select class="form-select" id="fuel_type" name="fuel_type">
                                            <option value="">Select Fuel Type</option>
                                            <option value="Diesel">Diesel</option>
                                            <option value="Gasoline">Gasoline</option>
                                            <option value="Electric">Electric</option>
                                            <option value="Hybrid">Hybrid</option>
                                        </select>
                                        <span class="text-danger error-text fuel_type_error"></span>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="condition" class="form-label">Condition</label>
                                        <select class="form-select" id="condition" name="condition">
                                            <option value="">Select Condition</option>
                                            <option value="New">New</option>
                                            <option value="Used">Used</option>
                                            <option value="Refurbished">Refurbished</option>
                                        </select>
                                        <span class="text-danger error-text condition_error"></span>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="serial_number" class="form-label">Serial Number</label>
                                        <input type="text" class="form-control" id="serial_number" name="serial_number" placeholder="Enter serial number">
                                        <span class="text-danger error-text serial_number_error"></span>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="buy_now_price" class="form-label">Buy Now Price ($) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="buy_now_price" name="buy_now_price" placeholder="Enter buy now price" step="0.01">
                                        <span class="text-danger error-text buy_now_price_error"></span>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="bid_start_price" class="form-label">Bid Start Price ($) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="bid_start_price" name="bid_start_price" placeholder="Enter bid start price" step="0.01" readonly>
                                        <span class="text-danger error-text bid_start_price_error"></span>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="bid_end_time" class="form-label">Bid End Date & Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="bid_end_time" name="bid_end_time">
                                        <span class="text-danger error-text bid_end_time_error"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Third Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Specifications</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Key</label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Value</label>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Action</label>
                                    </div>
                                </div>
                                <div id="specifications-container">
                                    <div class="row specification-row mb-2">
                                        <div class="col-md-5 mb-2">
                                            <input type="text" class="form-control" name="spec_keys[]" placeholder="Specification key">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <input type="text" class="form-control" name="spec_values[]" placeholder="Specification value">
                                        </div>
                                        <div class="col-md-1 mb-2">
                                            <button type="button" class="btn btn-danger remove-spec-btn"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-primary" id="add-spec-btn">
                                            <i class="fas fa-plus me-1"></i>Add Specification
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="window.location='{{ route('admin.machinery') }}'">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="saveMachineryBtn">
                                    <i class="fas fa-save me-1"></i>Save Machinery
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @include('Admin.Layouts.Footer')
                <div class="content-backdrop fade"></div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Quill editor
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });

        // Sync Quill content to hidden input
        quill.on('text-change', function() {
            document.getElementById('description').value = quill.root.innerHTML;
        });

        // Calculate bid start price (90% of buy now price)
        document.getElementById('buy_now_price').addEventListener('input', function() {
            const buyNowPrice = parseFloat(this.value) || 0;
            const bidStartPrice = buyNowPrice * 0.9;
            document.getElementById('bid_start_price').value = bidStartPrice.toFixed(2);
        });

        // Add specification row
        document.getElementById('add-spec-btn').addEventListener('click', function() {
            const container = document.getElementById('specifications-container');
            const newRow = document.createElement('div');
            newRow.className = 'row specification-row mb-2';
            newRow.innerHTML = `
                <div class="col-md-5 mb-2">
                    <input type="text" class="form-control" name="spec_keys[]" placeholder="Specification key">
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" class="form-control" name="spec_values[]" placeholder="Specification value">
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" class="btn btn-danger remove-spec-btn"><i class="fas fa-trash"></i></button>
                </div>
            `;
            container.appendChild(newRow);
            
            // Enable remove buttons for all rows
            updateRemoveButtons();
        });

        // Remove specification row
        document.getElementById('specifications-container').addEventListener('click', function(e) {
            if (e.target.closest('.remove-spec-btn')) {
                const row = e.target.closest('.specification-row');
                row.remove();
                
                // Ensure at least one row exists
                ensureMinimumSpecRow();
                
                // Update remove buttons state
                updateRemoveButtons();
            }
        });

        // Function to ensure at least one specification row exists
        function ensureMinimumSpecRow() {
            const container = document.getElementById('specifications-container');
            if (container.children.length === 0) {
                const newRow = document.createElement('div');
                newRow.className = 'row specification-row mb-2';
                newRow.innerHTML = `
                    <div class="col-md-5 mb-2">
                        <input type="text" class="form-control" name="spec_keys[]" placeholder="Specification key">
                    </div>
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control" name="spec_values[]" placeholder="Specification value">
                    </div>
                    <div class="col-md-1 mb-2">
                        <button type="button" class="btn btn-danger remove-spec-btn" disabled><i class="fas fa-trash"></i></button>
                    </div>
                `;
                container.appendChild(newRow);
            }
        }

        // Function to update remove buttons based on row count
        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.specification-row');
            const removeButtons = document.querySelectorAll('.remove-spec-btn');
            
            if (rows.length <= 1) {
                // Disable remove button if only one row exists
                removeButtons.forEach(button => {
                    button.disabled = true;
                });
            } else {
                // Enable all remove buttons
                removeButtons.forEach(button => {
                    button.disabled = false;
                });
            }
        }

        // Initialize with one row and proper button state
        ensureMinimumSpecRow();
        updateRemoveButtons();

        // Form submission
        document.getElementById('machineryForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Update description field with Quill content
            document.getElementById('description').value = quill.root.innerHTML;

            // Show loading indicator
            const saveBtn = document.getElementById('saveMachineryBtn');
            const originalBtnText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            saveBtn.disabled = true;

            // Clear previous errors
            document.querySelectorAll('.error-text').forEach(el => el.textContent = '');

            const formData = new FormData(this);

            fetch('{{ route("admin.machinery.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show success message
                    const alertHtml = `
                        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                            <strong>Success!</strong> ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    document.body.insertAdjacentHTML('beforeend', alertHtml);

                    // Reset form
                    this.reset();
                    quill.setContents([]);
                    document.getElementById('bid_start_price').value = '';

                    // Remove all specification rows except the first one
                    const specRows = document.querySelectorAll('.specification-row');
                    for (let i = 1; i < specRows.length; i++) {
                        specRows[i].remove();
                    }
                    // Clear the first row
                    specRows[0].querySelector('input[name="spec_keys[]"]').value = '';
                    specRows[0].querySelector('input[name="spec_values[]"]').value = '';

                    // Redirect after delay
                    setTimeout(() => {
                        window.location.href = '{{ route("admin.machinery") }}';
                    }, 2000);
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errorElement = document.querySelector(`.${field}_error`);
                            if (errorElement) {
                                errorElement.textContent = data.errors[field][0];
                            }
                        });
                    } else {
                        // Show general error message
                        const alertHtml = `
                            <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                <strong>Error!</strong> An error occurred while saving the machinery.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        document.body.insertAdjacentHTML('beforeend', alertHtml);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alertHtml = `
                    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                        <strong>Error!</strong> An error occurred while saving the machinery.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', alertHtml);
            })
            .finally(() => {
                // Restore button
                saveBtn.innerHTML = originalBtnText;
                saveBtn.disabled = false;
            });
        });
    });
</script>
@endsection