@extends('Admin.Particals.app')

@section('title', 'Edit Category')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('Admin.Layouts.Sidebar')

            <div class="layout-page">
                @include('Admin.Layouts.Navbar')

                <div class="content-wrapper">
                    <div class="mx-4 flex-grow-1 container-p-y">
                        <div class="card p-4">
                            <div class="d-flex mb-4">
                                <div class="w-50 text-start">
                                    <h4>Edit Category</h4>
                                </div>
                                <div class="w-50 text-end">
                                    <a href="{{ route('admin.categories') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-arrow-left me-1"></i>Back to Categories
                                    </a>
                                </div>
                            </div>

                            <form id="editCategoryForm" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="_method" value="PUT">
                                <input type="hidden" name="id" value="{{ $category->id }}">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="categoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="categoryName" name="category_name" placeholder="Enter category name" value="{{ $category->category_name }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="totalMachinery" class="form-label">Total Machinery <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="totalMachinery" name="total_machinery" placeholder="Enter total machinery" min="0" value="{{ $category->total_machinery }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="categoryImage" class="form-label">Category Image</label>
                                        <input type="file" class="form-control" id="categoryImage" name="image" accept="image/*">
                                        @if($category->image)
                                            <div class="mt-2">
                                                <label class="form-label">Current Image:</label>
                                                <br>
                                                <img src="{{ asset('categories/' . $category->image) }}" alt="Category Image" width="100" class="img-thumbnail">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-12 text-end">
                                        <button type="button" class="btn btn-secondary me-2" onclick="window.location='{{ route('admin.categories') }}'">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="updateCategoryBtn">
                                            <i class="fas fa-save me-1"></i>Update Category
                                        </button>
                                    </div>
                                </div>
                            </form>
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

    <script>
        $(document).ready(function() {
            $('#editCategoryForm').on('submit', function(e) {
                e.preventDefault();

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                const formData = new FormData(this);

                $('#updateCategoryBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Updating...');

                $.ajax({
                    url: "{{ route('admin.categories.update', $category->id) }}",
                    type: 'POST', // Using POST with _method=PUT for file uploads
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const alertHtml = `
                            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                <strong>Success!</strong> ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        $('body').append(alertHtml);

                        setTimeout(() => {
                            window.location.href = "{{ route('admin.categories') }}";
                        }, 2000);
                    },
                    error: function(xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                let fieldName = key.replace(/_([a-z])/g, function(g) {
                                    return g[1].toUpperCase();
                                });

                                if (key === 'total_machinery') {
                                    fieldName = 'totalMachinery';
                                }
                                if (key === 'image') {
                                    fieldName = 'categoryImage';
                                }

                                if (key === 'image') {
                                    const imageFieldElement = $('#categoryImage');
                                    imageFieldElement.addClass('is-invalid');
                                    $('#categoryImage').siblings('.invalid-feedback').remove();
                                    imageFieldElement.after(`<div class="invalid-feedback">${value[0]}</div>`);
                                } else {
                                    const fieldElement = $('#' + fieldName);
                                    fieldElement.addClass('is-invalid');
                                    fieldElement.after(`<div class="invalid-feedback">${value[0]}</div>`);
                                }
                            });
                        } else {
                            let errorMessage = 'An error occurred while updating the category.';
                            const alertHtml = `
                                <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                    <strong>Error!</strong> ${errorMessage}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            `;
                            $('body').append(alertHtml);

                            setTimeout(() => {
                                $('.alert').fadeOut('slow', function() {
                                    $(this).remove();
                                });
                            }, 5000);
                        }

                        $('#updateCategoryBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Update Category');
                    }
                });
            });
        });
    </script>
@endsection