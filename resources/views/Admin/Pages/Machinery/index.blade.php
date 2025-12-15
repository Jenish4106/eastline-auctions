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
                                    <a href="{{ route('admin.machinery.create') }}" class="btn btn-primary">
                                        <i class="fa-solid fa-plus me-1"></i>Add Machinery
                                    </a>
                                </div>
                            </div>

                            <div class="card-datatable table-responsive pt-0">
                                <table class="table w-100" id="machineryTable" style="width: 100% !important;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">No.</th>
                                            <th class="text-center">Image</th>
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
                            { data: 'image_thumb', orderable: false },
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
            
            // Delete machinery button click event (using event delegation)
            $(document).on('click', '.delete-machine', function () {
                var machineryId = $(this).data('id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete this machinery!",
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
        });
    </script>
@endsection