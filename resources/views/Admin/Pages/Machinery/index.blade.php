@extends('Admin.Particals.app')

@section('title', 'Machinery Management')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('Admin.Layouts.Sidebar')

            <div class="layout-page">
                @include('Admin.Layouts.Navbar')

                <div class="content-wrapper">
                    <div class="mx-4 flex-grow-1 container-p-y">
                        <div class="card p-4">
                            <div class="d-flex mb-1">
                                <div class="w-50 text-start">
                                    <h4>Machinery Management</h4>
                                </div>
                                <div class="w-50 text-end">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#machineryModal">
                                        <i class="fa-solid fa-plus me-1"></i>Add Machinery
                                    </button>
                                </div>
                            </div>

                            <div class="card-datatable table-responsive pt-0">
                                <table class="table w-100" id="machineryTable" style="width: 100% !important;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">No.</th>
                                            <th class="text-center">Image</th>
                                            <th class="text-center">Name</th>
                                            <th class="text-center">Category</th>
                                            <th class="text-center">Year</th>
                                            <th class="text-center">Working Hours</th>
                                            <th class="text-center">Buy Now Price</th>
                                            <th class="text-center">Bid Start Price</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center"></tbody>
                                </table>
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
    
    <!-- Add Machinery Modal -->
    <div class="modal fade" id="machineryModal" tabindex="-1" aria-labelledby="machineryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="machineryModalLabel">Add New Machinery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="machineryForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="machinery_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter machinery name">
                                <span class="text-danger error-text name_error"></span>
                            </div>
                            
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
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                                <select class="form-select" id="year" name="year">
                                    <option value="">Select Year</option>
                                    @for ($i = date('Y'); $i >= 1950; $i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                                <span class="text-danger error-text year_error"></span>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="weight" class="form-label">Weight <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="weight" name="weight" placeholder="Enter weight">
                                <span class="text-danger error-text weight_error"></span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="working_hours" class="form-label">Working Hours <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="working_hours" name="working_hours" placeholder="Enter working hours">
                                <span class="text-danger error-text working_hours_error"></span>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="condition" class="form-label">Condition <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="condition" name="condition" placeholder="Enter condition">
                                <span class="text-danger error-text condition_error"></span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fuel" class="form-label">Fuel <span class="text-danger">*</span></label>
                                <select class="form-select" id="fuel" name="fuel">
                                    <option value="">Select Fuel Type</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Gasoline">Gasoline</option>
                                    <option value="Electric">Electric</option>
                                    <option value="Hybrid">Hybrid</option>
                                    <option value="Other">Other</option>
                                </select>
                                <span class="text-danger error-text fuel_error"></span>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="buy_now_price" class="form-label">Buy Now Price <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="buy_now_price" name="buy_now_price" placeholder="Enter buy now price" step="0.01">
                                <span class="text-danger error-text buy_now_price_error"></span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bid_start_price" class="form-label">Bid Start Price <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="bid_start_price" name="bid_start_price" placeholder="Calculated automatically" step="0.01" readonly>
                                <span class="text-danger error-text bid_start_price_error"></span>
                                <small class="form-text text-muted">This value is automatically calculated as 90% of Buy Now Price</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="bid_end_time" class="form-label">Bid End Time <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="bid_end_time" name="bid_end_time" placeholder="Select bid end date and time">
                                <span class="text-danger error-text bid_end_time_error"></span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="2">Sold</option>
                                    <option value="3">Closed</option>
                                </select>
                                <span class="text-danger error-text status_error"></span>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="image" class="form-label">Images</label>
                                <input type="file" class="form-control" id="image" name="images[]" multiple>
                                <span class="text-danger error-text image_error"></span>
                                <div id="current-image-preview" class="mt-2"></div>
                                <small class="form-text text-muted">You can select multiple images</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <div id="description-editor" style="height: 200px;"></div>
                                <textarea class="form-control d-none" id="description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Specifications</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="specifications-table">
                                        <thead>
                                            <tr>
                                                <th width="40%">Key</th>
                                                <th width="50%">Value</th>
                                                <th width="10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control spec-key" value="Make" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control spec-value" placeholder="Enter Make">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-spec-row" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control spec-key" value="Model" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control spec-value" placeholder="Enter Model">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-spec-row" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control spec-key" value="Engine Power" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control spec-value" placeholder="Enter Engine Power">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-spec-row" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control spec-key" value="Transport Dimensions" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control spec-value" placeholder="Enter Transport Dimensions">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-spec-row" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control spec-key" value="Tracks" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control spec-value" placeholder="Enter Tracks">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-spec-row" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control spec-key" value="Transmission" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control spec-value" placeholder="Enter Transmission">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-spec-row" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3">
                                                    <button type="button" class="btn btn-primary btn-sm" id="add-spec-row">
                                                        <i class="fas fa-plus me-1"></i>Add More Specification
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <input type="hidden" id="specification" name="specification">
                                <span class="text-danger error-text specification_error"></span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Offers</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="offers-table">
                                        <thead>
                                            <tr>
                                                <th width="90%">Offer Text</th>
                                                <th width="10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control offer-text" placeholder="Enter offer details">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-offer-row" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="2">
                                                    <button type="button" class="btn btn-primary btn-sm" id="add-offer-row">
                                                        <i class="fas fa-plus me-1"></i>Add More Offer
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <input type="hidden" id="offer" name="offer">
                                <span class="text-danger error-text offer_error"></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveMachineryBtn">
                            <i class="fas fa-save me-1"></i>Save Machinery
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let table;
            
            // Helper function to validate URLs
            function isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (_) {
                    return false;
                }
            }
            
            function loadMachinery() {
                if (table) {
                    table.ajax.reload(null, false);
                } else {
                    table = $('#machineryTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('admin.machinery.fetch') }}",
                            type: 'GET'
                        },
                        columns: [
                            { data: 'DT_RowIndex', orderable: false },
                            { data: 'image_thumb', orderable: false },
                            { data: 'name' },
                            { data: 'category_name' },
                            { data: 'year' },
                            { data: 'working_hours' },
                            { data: 'buy_now_price' },
                            { data: 'bid_start_price' },
                            { data: 'status_badge' },
                            { data: 'actions', orderable: false }
                        ],
                        responsive: false,
                        paging: true,
                        searching: true,
                        lengthMenu: [
                            [10, 25, 50, 75, 100, -1],
                            [10, 25, 50, 75, 100, 'All']
                        ],
                        ordering: true,
                        autoWidth: false,
                        scrollX: false,
                        scrollCollapse: true
                    });
                }
            }

            loadMachinery();
            
            // Reset form and errors when modal is closed
            $('#machineryModal').on('hidden.bs.modal', function () {
                resetForm();
            });

            // Add new specification row
            $(document).on('click', '#add-spec-row', function() {
                var newRow = `
                    <tr>
                        <td>
                            <input type="text" class="form-control spec-key" placeholder="Enter key">
                        </td>
                        <td>
                            <input type="text" class="form-control spec-value" placeholder="Enter value">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-spec-row">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#specifications-table tbody').append(newRow);
            });

            // Remove specification row
            $(document).on('click', '.remove-spec-row', function() {
                $(this).closest('tr').remove();
            });

            // Add new offer row
            $(document).on('click', '#add-offer-row', function() {
                var newRow = `
                    <tr>
                        <td>
                            <input type="text" class="form-control offer-text" placeholder="Enter offer details">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-offer-row">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#offers-table tbody').append(newRow);
            });

            // Remove offer row
            $(document).on('click', '.remove-offer-row', function() {
                $(this).closest('tr').remove();
            });

            $('#machineryModal').on('shown.bs.modal', function () {
                $('.error-text').text('');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                
                // Add event listener to show image previews
                $('#image').off('change').on('change', function() {
                    var files = this.files;
                    var previewContainer = $('#current-image-preview');
                    
                    // Clear previous previews but keep existing images when editing
                    if (!$('#machinery_id').val()) {
                        previewContainer.html('');
                    }
                    
                    if (files.length > 0) {
                        var previewHtml = '<div class="d-flex flex-wrap gap-2 mt-2">';
                        var processed = 0;
                        var totalToProcess = Math.min(files.length, 10);
                        
                        for (var i = 0; i < totalToProcess; i++) {
                            var file = files[i];
                            if (file.type.match('image.*')) {
                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    previewHtml += '<img src="' + e.target.result + '" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">';
                                    processed++;
                                    if (processed === totalToProcess) {
                                        previewHtml += '</div>';
                                        previewContainer.append(previewHtml);
                                    }
                                };
                                reader.readAsDataURL(file);
                            } else {
                                processed++;
                                if (processed === totalToProcess) {
                                    previewHtml += '</div>';
                                    previewContainer.append(previewHtml);
                                }
                            }
                        }
                    }
                });
                
                // Remove any existing event handlers to prevent duplication
                $('#buy_now_price').off('input');
                
                // Add event listener to calculate bid start price
                $('#buy_now_price').on('input', function() {
                    var buyNowPrice = parseFloat($(this).val());
                    if (!isNaN(buyNowPrice) && buyNowPrice > 0) {
                        var bidStartPrice = (buyNowPrice * 0.9).toFixed(2);
                        $('#bid_start_price').val(bidStartPrice);
                    } else {
                        // Only clear if we're adding, preserve existing value when editing
                        if (!$('#machinery_id').val()) {
                            $('#bid_start_price').val('');
                        }
                    }
                });
                
                if (window.bidEndTimePicker) {
                    window.bidEndTimePicker.destroy();
                }
                
                window.bidEndTimePicker = flatpickr("#bid_end_time", {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    time_24hr: false,
                    minuteIncrement: 1,
                    defaultHour: 12,
                    defaultMinute: 0
                });
                
                if (window.quillEditor) {
                    window.quillEditor.setText('');
                } else {
                    window.quillEditor = new Quill('#description-editor', {
                        modules: {
                            toolbar: [
                                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                [{ 'align': [] }],
                                ['link', 'image'],
                                ['clean']
                            ]
                        },
                        placeholder: 'Enter machinery description...',
                        theme: 'snow'
                    });
                }
                
                // Only set default title if we're not editing (no machinery ID)
                if (!$('#machinery_id').val()) {
                    $('#machineryModalLabel').text('Add New Machinery');
                    $('#machineryForm')[0].reset();
                    $('#machinery_id').val('');
                    $('#current-image-preview').html('');
                    $('#saveMachineryBtn').html('<i class="fas fa-save me-1"></i>Save Machinery');
                }
            });

            // Save machinery form submission
            $('#machineryForm').on('submit', function (e) {
                e.preventDefault();
                
                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                $('.error-text').text('');
                
                // Client-side validation
                let isValid = true;
                const isEditing = $('#machinery_id').val() !== ''; // Check if we're editing
                
                // Validate required fields
                const requiredFields = [
                    { id: 'name', name: 'Name' },
                    { id: 'category_id', name: 'Category' },
                    { id: 'year', name: 'Year' },
                    { id: 'weight', name: 'Weight' },
                    { id: 'working_hours', name: 'Working Hours' },
                    { id: 'condition', name: 'Condition' },
                    { id: 'fuel', name: 'Fuel' },
                    { id: 'buy_now_price', name: 'Buy Now Price' },
                    { id: 'bid_end_time', name: 'Bid End Time' },
                    { id: 'status', name: 'Status' }
                ];
                
                requiredFields.forEach(function(field) {
                    const fieldElement = $('#' + field.id);
                    if (!fieldElement.val()) {
                        isValid = false;
                        fieldElement.addClass('is-invalid');
                        fieldElement.after(`<div class="invalid-feedback">${field.name} is required.</div>`);
                    }
                });
                
                // Validate image (required when adding, optional when editing if already exists)
                const imageField = $('#image');
                const imageFiles = imageField[0].files;
                const hasExistingImages = $('#current-image-preview').find('img').length > 0;
                
                // Image is required when adding new machinery or when editing but no existing images
                if (!isEditing || (isEditing && !hasExistingImages)) {
                    if (imageFiles.length === 0) {
                        isValid = false;
                        imageField.addClass('is-invalid');
                        imageField.after(`<div class="invalid-feedback">At least one image is required.</div>`);
                    } else if (imageFiles.length > 10) {
                        isValid = false;
                        imageField.addClass('is-invalid');
                        imageField.after(`<div class="invalid-feedback">You may not upload more than 10 images.</div>`);
                    } else {
                        // Validate file extensions when images are provided
                        for (let i = 0; i < imageFiles.length; i++) {
                            const fileName = imageFiles[i].name;
                            const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.gif)$/i;
                            if (!allowedExtensions.exec(fileName)) {
                                isValid = false;
                                imageField.addClass('is-invalid');
                                imageField.after(`<div class="invalid-feedback">Please select valid image files (jpg, jpeg, png, gif).</div>`);
                                break;
                            }
                        }
                    }
                } else if (imageFiles.length > 0) {
                    // If editing and providing new images
                    if (imageFiles.length > 10) {
                        isValid = false;
                        imageField.addClass('is-invalid');
                        imageField.after(`<div class="invalid-feedback">You may not upload more than 10 images.</div>`);
                    } else {
                        // Validate file extensions when images are provided
                        for (let i = 0; i < imageFiles.length; i++) {
                            const fileName = imageFiles[i].name;
                            const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.gif)$/i;
                            if (!allowedExtensions.exec(fileName)) {
                                isValid = false;
                                imageField.addClass('is-invalid');
                                imageField.after(`<div class="invalid-feedback">Please select valid image files (jpg, jpeg, png, gif).</div>`);
                                break;
                            }
                        }
                    }
                }
                
                // Validate numeric fields
                const numericFields = [
                    { id: 'year', name: 'Year' },
                    { id: 'weight', name: 'Weight' },
                    { id: 'working_hours', name: 'Working Hours' },
                    { id: 'buy_now_price', name: 'Buy Now Price' },
                    { id: 'bid_start_price', name: 'Bid Start Price' }
                ];
                
                numericFields.forEach(function(field) {
                    const fieldElement = $('#' + field.id);
                    const value = fieldElement.val();
                    if (value && (isNaN(value) || parseFloat(value) <= 0)) {
                        isValid = false;
                        fieldElement.addClass('is-invalid');
                        fieldElement.after(`<div class="invalid-feedback">${field.name} must be a positive number.</div>`);
                    }
                });
                
                if (window.quillEditor && !window.quillEditor.getText().trim()) {
                    isValid = false;
                    $('#description-editor').addClass('is-invalid');
                    $('#description-editor').after(`<div class="invalid-feedback">Description is required.</div>`);
                }
                
                let specValid = false;
                let specError = false;
                $('#specifications-table tbody tr').each(function() {
                    const key = $(this).find('.spec-key').val();
                    const value = $(this).find('.spec-value').val();
                    
                    if (key && value) {
                        specValid = true;
                    }
                    
                    if (key && !value) {
                        specError = true;
                        $(this).find('.spec-value').addClass('is-invalid');
                        $(this).find('.spec-value').after(`<div class="invalid-feedback">Value is required.</div>`);
                    }
                    
                    if (value && !key) {
                        specError = true;
                        $(this).find('.spec-key').addClass('is-invalid');
                        $(this).find('.spec-key').after(`<div class="invalid-feedback">Key is required.</div>`);
                    }
                });
                
                if (!specValid && !specError) {
                    isValid = false;
                    $('.specification_error').text('At least one specification value is required.');
                }
                
                // Validate offer fields (at least one offer should be filled)
                let offerValid = false;
                let offerError = false;
                $('#offers-table tbody tr').each(function() {
                    const text = $(this).find('.offer-text').val();
                    if (text) {
                        offerValid = true;
                    }
                    // Check for empty values
                    if (!text && $(this).find('.offer-text').length > 0) {
                        offerError = true;
                        $(this).find('.offer-text').addClass('is-invalid');
                        $(this).find('.offer-text').after(`<div class="invalid-feedback">Offer text is required.</div>`);
                    }
                });
                
                if (!offerValid && !offerError) {
                    isValid = false;
                    $('.offer_error').text('At least one offer is required.');
                }
                
                // Validate bid_end_time is a future date
                const bidEndTimeField = $('#bid_end_time');
                const bidEndTimeValue = bidEndTimeField.val();
                if (bidEndTimeValue) {
                    const bidEndDate = new Date(bidEndTimeValue);
                    const currentDate = new Date();
                    if (bidEndDate <= currentDate) {
                        isValid = false;
                        bidEndTimeField.addClass('is-invalid');
                        bidEndTimeField.after(`<div class="invalid-feedback">Bid end time must be a future date.</div>`);
                    }
                }
                
                // If validation fails, stop submission
                if (!isValid) {
                    return;
                }
                
                // Sync Quill editor content to hidden textarea
                if (window.quillEditor) {
                    $('#description').val(window.quillEditor.getText().trim() ? window.quillEditor.root.innerHTML : '');
                }
                
                // Sync specifications to hidden input as JSON
                var specifications = {};
                $('#specifications-table tbody tr').each(function() {
                    var key = $(this).find('.spec-key').val();
                    var value = $(this).find('.spec-value').val();
                    if (key && value) {
                        specifications[key] = value;
                    }
                });
                $('#specification').val(JSON.stringify(specifications));
                
                // Sync offers to hidden input as JSON
                var offers = {};
                $('#offers-table tbody tr').each(function() {
                    var text = $(this).find('.offer-text').val();
                    if (text) {
                        // Use a simple index as key since we don't have explicit keys
                        var index = Object.keys(offers).length;
                        offers[index] = text;
                    }
                });
                $('#offer').val(JSON.stringify(offers));
                
                var formData = new FormData(this);
                var machineryId = $('#machinery_id').val();
                var url = machineryId ? "{{ route('admin.machinery.update', ['id' => '__ID__']) }}".replace('__ID__', machineryId) : "{{ route('admin.machinery.store') }}";
                
                // Handle multiple image files
                if (imageFiles.length > 0) {
                    // Remove the original image field from formData
                    formData.delete('images[]');
                    
                    // Append each image file individually
                    for (let i = 0; i < imageFiles.length; i++) {
                        formData.append('images[]', imageFiles[i]);
                    }
                }
                
                $('#saveMachineryBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');
                
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        // Show success message
                        const alertHtml = `
                            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                <strong>Success!</strong> ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        $('body').append(alertHtml);
                        
                        // Reset form and close modal
                        resetForm();
                        $('#machineryModal').modal('hide');
                        
                        // Reload table
                        table.ajax.reload();
                        
                        // Re-enable submit button
                        $('#saveMachineryBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save Machinery');
                        
                        // Auto-dismiss alert after 3 seconds
                        setTimeout(() => {
                            $('.alert').fadeOut('slow', function() {
                                $(this).remove();
                            });
                        }, 3000);
                    },
                    error: function (xhr) {
                        // Show field-wise errors
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                // Handle special cases for field names
                                let fieldName = key;
                                if (key === 'category_id') {
                                    fieldName = 'category_id';
                                } else if (key === 'buy_now_price') {
                                    fieldName = 'buy_now_price';
                                } else if (key === 'bid_start_price') {
                                    fieldName = 'bid_start_price';
                                } else if (key === 'bid_end_time') {
                                    fieldName = 'bid_end_time';
                                } else if (key === 'working_hours') {
                                    fieldName = 'working_hours';
                                } else if (key === 'condition') {
                                    fieldName = 'condition';
                                } else if (key === 'fuel') {
                                    fieldName = 'fuel';
                                } else if (key === 'specification') {
                                    fieldName = 'specification';
                                } else if (key === 'offer') {
                                    fieldName = 'offer';
                                } else if (key === 'images') {
                                    fieldName = 'image';
                                }
                                
                                const fieldElement = $('#' + fieldName);
                                fieldElement.addClass('is-invalid');
                                fieldElement.after(`<div class="invalid-feedback">${value[0]}</div>`);
                            });
                        } else {
                            // Show general error message
                            let errorMessage = 'An error occurred while saving the machinery.';
                            const alertHtml = `
                                <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                    <strong>Error!</strong> ${errorMessage}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            `;
                            $('body').append(alertHtml);
                            
                            // Auto-dismiss alert after 5 seconds
                            setTimeout(() => {
                                $('.alert').fadeOut('slow', function() {
                                    $(this).remove();
                                });
                            }, 5000);
                        }
                        
                        // Re-enable submit button
                        $('#saveMachineryBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save Machinery');
                    }
                });
            });

            // Edit machinery button click event (using event delegation)
            $(document).on('click', '.edit-machine', function () {
                var machineryId = $(this).data('id');
                
                $.ajax({
                    url: "{{ route('admin.machinery.get') }}",
                    type: 'GET',
                    data: { id: machineryId },
                    success: function (response) {
                        var machinery = response.machinery;
                        
                        $('#machineryModalLabel').text('Edit Machinery');
                        $('#machinery_id').val(machinery.id);
                        $('#name').val(machinery.name);
                        $('#category_id').val(machinery.category_id);
                        $('#year').val(machinery.year);
                        $('#weight').val(machinery.weight);
                        $('#working_hours').val(machinery.working_hours);
                        $('#condition').val(machinery.condition);
                        $('#fuel').val(machinery.fuel);
                        $('#buy_now_price').val(machinery.buy_now_price);
                        $('#bid_start_price').val(machinery.bid_start_price);
                        $('#status').val(machinery.status);
                        $('#description').val(machinery.description);
                        
                        // Trigger the calculation for bid start price based on buy now price
                        if (machinery.buy_now_price) {
                            var buyNowPrice = parseFloat(machinery.buy_now_price);
                            if (!isNaN(buyNowPrice) && buyNowPrice > 0) {
                                var bidStartPrice = (buyNowPrice * 0.9).toFixed(2);
                                $('#bid_start_price').val(bidStartPrice);
                            }
                        } else {
                            // Clear the bid start price if no buy now price
                            $('#bid_start_price').val('');
                        }
                        
                        // Handle JSON fields
                        if (machinery.specification) {
                            $('#specification').val(JSON.stringify(machinery.specification, null, 2));
                            
                            // Populate specification table
                            var specTableBody = $('#specifications-table tbody');
                            // Clear existing dynamic rows (keep default rows)
                            specTableBody.find('tr:not(:lt(6))').remove();
                            
                            // Populate default rows and add new ones if needed
                            var defaultKeys = ['Make', 'Model', 'Engine Power', 'Transport Dimensions', 'Tracks', 'Transmission'];
                            var specData = typeof machinery.specification === 'string' ? 
                                          JSON.parse(machinery.specification) : machinery.specification;
                            
                            // Fill default rows
                            specTableBody.find('tr').each(function(index) {
                                if (index < defaultKeys.length) {
                                    var key = defaultKeys[index];
                                    if (specData && specData[key]) {
                                        $(this).find('.spec-value').val(specData[key]);
                                    }
                                }
                            });
                            
                            // Add additional rows for any extra specifications
                            if (specData) {
                                $.each(specData, function(key, value) {
                                    if (!defaultKeys.includes(key)) {
                                        var newRow = `
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control spec-key" value="${key}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control spec-value" value="${value}">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-spec-row">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `;
                                        specTableBody.append(newRow);
                                    }
                                });
                            }
                        }
                        if (machinery.offer) {
                            $('#offer').val(JSON.stringify(machinery.offer, null, 2));
                            
                            // Populate offer table
                            var offerTableBody = $('#offers-table tbody');
                            // Clear existing rows except the first one
                            offerTableBody.find('tr:not(:first)').remove();
                            
                            // Parse offer data
                            var offerData = typeof machinery.offer === 'string' ? 
                                          JSON.parse(machinery.offer) : machinery.offer;
                            
                            // Fill first row
                            var firstRow = offerTableBody.find('tr:first');
                            var firstKey = Object.keys(offerData)[0];
                            if (firstKey !== undefined) {
                                firstRow.find('.offer-text').val(offerData[firstKey]);
                                
                                // Add additional rows for any extra offers
                                var isFirst = true;
                                $.each(offerData, function(key, value) {
                                    if (!isFirst) {
                                        var newRow = `
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control offer-text" value="${value}">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-offer-row">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `;
                                        offerTableBody.append(newRow);
                                    }
                                    isFirst = false;
                                });
                            } else {
                                // Clear first row if no data
                                firstRow.find('.offer-text').val('');
                            }
                        }
                        
                        // Clear previous errors
                        $('.error-text').text('');
                        $('.is-invalid').removeClass('is-invalid');
                        $('.invalid-feedback').remove();
                        
                        // Show current image preview if exists
                        if (machinery.image) {
                            // Parse image field in both JSON and comma-separated formats
                            var images = [];
                            if (typeof machinery.image === 'string') {
                                // Handle escaped JSON strings
                                var imageField = machinery.image;
                                if (imageField.match(/^"/) && imageField.match(/"\]$/)) {
                                    // Remove surrounding quotes and unescape
                                    imageField = imageField.trim().slice(1, -1).replace(/\\"/g, '"');
                                }
                                
                                if (imageField.charAt(0) === '[') {
                                    // JSON format (old format)
                                    try {
                                        images = JSON.parse(imageField);
                                    } catch (e) {
                                        images = [];
                                    }
                                } else if (imageField.charAt(0) === '{') {
                                    // Comma-separated format (new format)
                                    var imageString = imageField.replace(/[{}]/g, '');
                                    images = imageString.split(', ').map(function(item) {
                                        return item.trim();
                                    }).filter(function(item) {
                                        return item !== '';
                                    });
                                } else {
                                    // Single image name (fallback)
                                    images = [imageField];
                                }
                            } else if (Array.isArray(machinery.image)) {
                                // Already an array
                                images = machinery.image;
                            }
                            
                            if (Array.isArray(images) && images.length > 0) {
                                var previewHtml = '<div class="d-flex flex-wrap gap-2">';
                                images.forEach(function(img) {
                                    var imageUrl = "{{ asset('machinery') }}/" + img;
                                    previewHtml += '<img src="' + imageUrl + '" alt="Current Image" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">';
                                });
                                previewHtml += '</div>';
                                $('#current-image-preview').html(previewHtml);
                            } else {
                                $('#current-image-preview').html('');
                            }
                        } else {
                            $('#current-image-preview').html('');
                        }
                        
                        // Initialize Quill editor with existing content
                        setTimeout(function() {
                            if (window.quillEditor) {
                                window.quillEditor.root.innerHTML = machinery.description || '';
                            } else {
                                window.quillEditor = new Quill('#description-editor', {
                                    modules: {
                                        toolbar: [
                                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                                            ['bold', 'italic', 'underline', 'strike'],
                                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                            [{ 'align': [] }],
                                            ['link', 'image'],
                                            ['clean']
                                        ]
                                    },
                                    placeholder: 'Enter machinery description...',
                                    theme: 'snow'
                                });
                                window.quillEditor.root.innerHTML = machinery.description || '';
                            }
                        }, 150);
                        
                        $('#saveMachineryBtn').html('<i class="fas fa-save me-1"></i>Update Machinery');
                        $('#machineryModal').modal('show');
                        
                        // Initialize Flatpickr with existing value after modal is shown
                        setTimeout(function() {
                            // Destroy any existing flatpickr instance
                            if (window.bidEndTimePicker) {
                                window.bidEndTimePicker.destroy();
                            }
                            
                            // Initialize new flatpickr instance with existing value
                            window.bidEndTimePicker = flatpickr("#bid_end_time", {
                                enableTime: true,
                                dateFormat: "Y-m-d H:i",
                                time_24hr: false,
                                minuteIncrement: 1,
                                defaultHour: 12,
                                defaultMinute: 0,
                                defaultDate: machinery.bid_end_time ? machinery.bid_end_time : null
                            });
                        }, 100);
                    },
                    error: function () {
                        const alertHtml = `
                            <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                <strong>Error!</strong> Failed to fetch machinery details.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        $('body').append(alertHtml);
                        
                        // Auto-dismiss alert after 3 seconds
                        setTimeout(() => {
                            $('.alert').fadeOut('slow', function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                });
            });

            // Delete machinery button click event (using event delegation)
            $(document).on('click', '.delete-machine', function () {
                var machineryId = $(this).data('id');
                var machineryName = $(this).data('name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete '" + machineryName + "' machinery!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, cancel!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.machinery.delete') }}",
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                id: machineryId
                            },
                            success: function (response) {
                                const alertHtml = `
                                    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Success!</strong> ${response.success}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                
                                // Reload table
                                table.ajax.reload();
                                
                                // Auto-dismiss alert after 3 seconds
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                            },
                            error: function () {
                                const alertHtml = `
                                    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Error!</strong> Failed to delete machinery.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                
                                // Auto-dismiss alert after 3 seconds
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                            }
                        });
                    }
                });
            });

            // Reset form function
            function resetForm() {
                $('#machineryForm')[0].reset();
                $('#machinery_id').val('');
                $('.error-text').text('');
                $('#current-image-preview').html('');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                
                // Reset calculated bid start price
                $('#bid_start_price').val('');
                
                // Reset modal title and button text
                $('#machineryModalLabel').text('Add New Machinery');
                $('#saveMachineryBtn').html('<i class="fas fa-save me-1"></i>Save Machinery');
                
                // Reset Quill editor
                if (window.quillEditor) {
                    window.quillEditor.setText('');
                }
                
                // Reset specification table to default state
                var specTableBody = $('#specifications-table tbody');
                // Clear all rows except the default 6
                specTableBody.find('tr:not(:lt(6))').remove();
                // Clear all values in default rows
                specTableBody.find('tr').each(function(index) {
                    $(this).find('.spec-value').val('');
                });
                
                // Reset offer table to default state
                var offerTableBody = $('#offers-table tbody');
                // Clear all rows except the first one
                offerTableBody.find('tr:not(:first)').remove();
                // Clear value in first row
                offerTableBody.find('tr:first .offer-text').val('');
                
                // Destroy Flatpickr instance if it exists
                if (window.bidEndTimePicker) {
                    window.bidEndTimePicker.destroy();
                    window.bidEndTimePicker = null;
                }
            }
        });
    </script>
@endsection