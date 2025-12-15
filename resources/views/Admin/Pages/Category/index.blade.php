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
                                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                                        <i class="fa-solid fa-plus me-1"></i>Add Category
                                    </a>
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

    <!-- Image Popup Modal -->
    <div class="modal fade" id="imagePopupModal" tabindex="-1" aria-labelledby="imagePopupModalLabel"
        aria-hidden="true">
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
                        serverSide: true,  // Enable server-side processing
                        ajax: {
                            url: "{{ route('admin.categories.fetch') }}",
                            type: 'GET'
                        },
                        columns: [
                            { data: 'DT_RowIndex', orderable: false },  // Make DT_RowIndex non-orderable
                            { data: 'image' },
                            { data: 'category_name' },
                            { data: 'total_machinery' },
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

            loadCategories();

            $(document).on('click', '.clickable-image', function() {
                const imageUrl = $(this).data('src');
                const categoryName = $(this).data('name');

                $('#popupImage').attr('src', imageUrl);
                $('#imagePopupModalLabel').text(categoryName + ' - Image Preview');
                $('#imagePopupModal').modal('show');
            });

            $(document).on('click', '.edit-category', function() {
                const categoryId = $(this).data('id');
                // Redirect to edit page instead of opening modal
                window.location.href = "{{ route('admin.categories.edit', '__ID__') }}".replace('__ID__', categoryId);
            });

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
        });
    </script>
@endsection
