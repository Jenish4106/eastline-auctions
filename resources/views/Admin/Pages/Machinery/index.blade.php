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
                                            <th class="text-center">Name</th>
                                            <th class="text-center">Category</th>
                                            <th class="text-center">Year</th>
                                            <th class="text-center">Weight</th>
                                            <th class="text-center">Fuel Type</th>
                                            <th class="text-center">Buy Now Price</th>
                                            <th class="text-center">Bid Start Price</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Created Date</th>
                                            <th class="text-center">Updated Date</th>
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
        <div class="modal-dialog modal-lg">
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
                                <input type="text" class="form-control" id="year" name="year" placeholder="Enter year">
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
                                <label for="fuel_type" class="form-label">Fuel Type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="fuel_type" name="fuel_type" placeholder="Enter fuel type">
                                <span class="text-danger error-text fuel_type_error"></span>
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
                                <input type="number" class="form-control" id="bid_start_price" name="bid_start_price" placeholder="Enter bid start price" step="0.01">
                                <span class="text-danger error-text bid_start_price_error"></span>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="bid_end_time" class="form-label">Bid End Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="bid_end_time" name="bid_end_time">
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
                                <label for="images" class="form-label">Images</label>
                                <input type="file" class="form-control" id="images" name="images">
                                <span class="text-danger error-text images_error"></span>
                                <div id="current-image-preview" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description"></textarea>
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
                            { data: 'name' },
                            { data: 'category_name' },
                            { data: 'year' },
                            { data: 'weight' },
                            { data: 'fuel_type' },
                            { data: 'buy_now_price' },
                            { data: 'bid_start_price' },
                            { data: 'status_badge' },
                            { data: 'created_date' },
                            { data: 'updated_date' },
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

            // Add new machinery button click event
            $('#machineryModal').on('shown.bs.modal', function () {
                $('#machineryModalLabel').text('Add New Machinery');
                $('#machineryForm')[0].reset();
                $('#machinery_id').val('');
                $('.error-text').text('');
                $('#current-image-preview').html('');
                $('#saveMachineryBtn').html('<i class="fas fa-save me-1"></i>Save Machinery');
            });

            // Save machinery form submission
            $('#machineryForm').on('submit', function (e) {
                e.preventDefault();
                
                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                $('.error-text').text('');
                
                var formData = new FormData(this);
                var machineryId = $('#machinery_id').val();
                var url = machineryId ? "{{ route('admin.machinery.update', ['id' => '__ID__']) }}".replace('__ID__', machineryId) : "{{ route('admin.machinery.store') }}";
                
                // Disable submit button and show loading
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
                        $('#machineryForm')[0].reset();
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
                                } else if (key === 'fuel_type') {
                                    fieldName = 'fuel_type';
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
                        $('#fuel_type').val(machinery.fuel_type);
                        $('#buy_now_price').val(machinery.buy_now_price);
                        $('#bid_start_price').val(machinery.bid_start_price);
                        $('#bid_end_time').val(machinery.bid_end_time);
                        $('#status').val(machinery.status);
                        $('#description').val(machinery.description);
                        
                        // Clear previous errors
                        $('.error-text').text('');
                        
                        // Show current image preview if exists
                        if (machinery.images) {
                            var imageUrl = "{{ asset('machinery') }}/" + machinery.images;
                            $('#current-image-preview').html('<img src="' + imageUrl + '" alt="Current Image" class="img-thumbnail" style="max-width: 200px;">');
                        } else {
                            $('#current-image-preview').html('');
                        }
                        
                        $('#saveMachineryBtn').html('<i class="fas fa-save me-1"></i>Update Machinery');
                        $('#machineryModal').modal('show');
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
            }
        });
    </script>
@endsection