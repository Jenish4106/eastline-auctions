@extends('Admin.Particals.app')

@section('title', 'Category Management')

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
                                    <h4>Category Management</h4>
                                </div>
                                <div class="w-50 text-end">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                        <i class="fa-solid fa-plus me-1"></i>Add Category
                                    </button>
                                </div>
                            </div>

                            <div class="card-datatable table-responsive pt-0">
                                <table class="table w-100" id="categoriesTable" style="width: 100% !important;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">No.</th>
                                            <th class="text-center">Image</th>
                                            <th class="text-center">Category Name</th>
                                            <th class="text-center">Total Machinery</th>
                                            <th class="text-center">Created Date</th>
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
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addCategoryForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="categoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="categoryName" name="category_name" placeholder="Enter category name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="totalMachinery" class="form-label">Total Machinery <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="totalMachinery" name="total_machinery" placeholder="Enter total machinery" min="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="categoryImage" class="form-label">Category Image</label>
                                <input type="file" class="form-control" id="categoryImage" name="image" accept="image/*">
                                <div class="form-text">Upload an image for the category (optional). Max size: 2MB. Supported formats: JPEG, PNG, JPG, GIF, SVG.</div>
                                <div class="invalid-feedback" id="categoryImageError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveCategoryBtn">
                            <i class="fas fa-save me-1"></i>Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCategoryForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="editCategoryId" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editCategoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editCategoryName" name="category_name" placeholder="Enter category name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editTotalMachinery" class="form-label">Total Machinery <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="editTotalMachinery" name="total_machinery" placeholder="Enter total machinery" min="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="editCategoryImage" class="form-label">Category Image</label>
                                <input type="file" class="form-control" id="editCategoryImage" name="image" accept="image/*">
                                <div class="form-text">Upload an image for the category (optional). Max size: 2MB. Supported formats: JPEG, PNG, JPG, GIF, SVG.</div>
                                <div id="currentImageContainer" class="mt-2"></div>
                                <!-- Validation error display for image -->
                                <div class="invalid-feedback" id="editCategoryImageError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="updateCategoryBtn">
                            <i class="fas fa-save me-1"></i>Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Popup Modal -->
    <div class="modal fade" id="imagePopupModal" tabindex="-1" aria-labelledby="imagePopupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagePopupModalLabel">Category Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="popupImage" src="" alt="Category Image" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let table;

            function loadCategories() {
                if (table) {
                    table.ajax.reload(null, false);
                } else {
                    table = $('#categoriesTable').DataTable({
                        processing: true,
                        serverSide: false,
                        ajax: {
                            url: "{{ route('admin.categories.fetch') }}",
                            dataSrc: 'data'
                        },
                        columns: [
                            { data: 'DT_RowIndex' },
                            { data: 'image' },
                            { data: 'category_name' },
                            { data: 'total_machinery' },
                            { data: 'created_date' },
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

            loadCategories();
            
            // Handle image click to show in popup
            $(document).on('click', '.clickable-image', function() {
                const imageUrl = $(this).data('src');
                const categoryName = $(this).data('name');
                
                $('#popupImage').attr('src', imageUrl);
                $('#imagePopupModalLabel').text(categoryName + ' - Image Preview');
                $('#imagePopupModal').modal('show');
            });
            
            // Handle Delete Category
            $(document).on('click', '.delete-category', function() {
                const categoryId = $(this).data('id');
                const categoryName = $(this).data('name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete category: ${categoryName}. This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.categories.delete') }}",
                            type: 'POST',
                            data: {
                                id: categoryId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                const alertHtml = `
                                    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Success!</strong> ${response.success}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Error!',
                                    'There was an error deleting the category.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
            
            // Handle Edit Category
            $(document).on('click', '.edit-category', function() {
                const categoryId = $(this).data('id');
                
                $.ajax({
                    url: "{{ route('admin.categories.get') }}",
                    type: 'GET',
                    data: {
                        id: categoryId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        const category = response.category;
                        
                        $('#editCategoryId').val(category.id);
                        $('#editCategoryName').val(category.category_name);
                        $('#editTotalMachinery').val(category.total_machinery);
                        
                        // Display current image if exists
                        if (category.image) {
                            $('#currentImageContainer').html(`
                                <div class="mt-2">
                                    <label class="form-label">Current Image:</label>
                                    <br>
                                    <img src="/storage/${category.image}" alt="Category Image" width="100" class="img-thumbnail">
                                </div>
                            `);
                        } else {
                            $('#currentImageContainer').html('');
                        }
                        
                        $('#editCategoryModal').modal('show');
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            'There was an error fetching the category details.',
                            'error'
                        );
                    }
                });
            });
            
            // Handle Add Category Form Submission
            $('#addCategoryForm').on('submit', function(e) {
                e.preventDefault();
                
                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                $('#categoryImageError').text('').hide();
                
                // Get form data
                const formData = new FormData(this);
                
                // Disable submit button and show loading
                $('#saveCategoryBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');
                
                // Submit form via AJAX
                $.ajax({
                    url: "{{ route('admin.categories.store') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Show success message
                        const alertHtml = `
                            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                <strong>Success!</strong> ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        $('body').append(alertHtml);
                        
                        // Reset form and close modal
                        $('#addCategoryForm')[0].reset();
                        $('#addCategoryModal').modal('hide');
                        
                        // Reload table
                        table.ajax.reload();
                        
                        // Re-enable submit button
                        $('#saveCategoryBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save Category');
                        
                        // Auto-dismiss alert after 3 seconds
                        setTimeout(() => {
                            $('.alert').fadeOut('slow', function() {
                                $(this).remove();
                            });
                        }, 3000);
                    },
                    error: function(xhr) {
                        // Show field-wise errors
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                // Convert snake_case to camelCase for field matching
                                let fieldName = key.replace(/_([a-z])/g, function (g) { return g[1].toUpperCase(); });
                                // Handle special cases
                                if (key === 'total_machinery') {
                                    fieldName = 'totalMachinery';
                                }
                                
                                // Handle image field specifically
                                if (key === 'image') {
                                    const imageFieldElement = $('#categoryImage');
                                    imageFieldElement.addClass('is-invalid');
                                    $('#categoryImageError').text(value[0]).show();
                                } else {
                                    const fieldElement = $('#' + fieldName);
                                    fieldElement.addClass('is-invalid');
                                    fieldElement.after(`<div class="invalid-feedback">${value[0]}</div>`);
                                }
                            });
                        } else {
                            // Show general error message
                            let errorMessage = 'An error occurred while saving the category.';
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
                        $('#saveCategoryBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save Category');
                    }
                });
            });
            
            // Handle Edit Category Form Submission
            $('#editCategoryForm').on('submit', function(e) {
                e.preventDefault();
                
                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                $('#editCategoryImageError').text('').hide();
                
                // Get form data
                const formData = new FormData(this);
                const categoryId = $('#editCategoryId').val();
                
                // Disable submit button and show loading
                $('#updateCategoryBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Updating...');
                
                // Submit form via AJAX
                $.ajax({
                    url: `{{ route('admin.categories.update', ['id' => '__ID__']) }}`.replace('__ID__', categoryId),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Show success message
                        const alertHtml = `
                            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                <strong>Success!</strong> ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        $('body').append(alertHtml);
                        
                        // Close modal
                        $('#editCategoryModal').modal('hide');
                        
                        // Reload table
                        table.ajax.reload();
                        
                        // Re-enable submit button
                        $('#updateCategoryBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Update Category');
                        
                        // Auto-dismiss alert after 3 seconds
                        setTimeout(() => {
                            $('.alert').fadeOut('slow', function() {
                                $(this).remove();
                            });
                        }, 3000);
                    },
                    error: function(xhr) {
                        // Show field-wise errors
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                // Convert snake_case to camelCase for field matching
                                let fieldName = key.replace(/_([a-z])/g, function (g) { return g[1].toUpperCase(); });
                                // Handle special cases
                                if (key === 'total_machinery') {
                                    fieldName = 'editTotalMachinery';
                                }
                                
                                // Handle image field specifically
                                if (key === 'image') {
                                    const imageFieldElement = $('#editCategoryImage');
                                    imageFieldElement.addClass('is-invalid');
                                    $('#editCategoryImageError').text(value[0]).show();
                                } else {
                                    const fieldElement = $('#' + fieldName);
                                    fieldElement.addClass('is-invalid');
                                    fieldElement.after(`<div class="invalid-feedback">${value[0]}</div>`);
                                }
                            });
                        } else {
                            // Show general error message
                            let errorMessage = 'An error occurred while updating the category.';
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
                        $('#updateCategoryBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Update Category');
                    }
                });
            });
        });
    </script>
@endsection