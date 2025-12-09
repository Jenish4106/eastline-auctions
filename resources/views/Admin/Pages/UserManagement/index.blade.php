@extends('Admin.Particals.app')

@section('title', 'User Management')

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
                                    <h4>User Management</h4>
                                </div>
                            </div>

                            <div class="card-datatable table-responsive pt-0">
                                <table class="table w-100" id="usersTable" style="width: 100% !important;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">ID</th>
                                            <th class="text-center">Name</th>
                                            <th class="text-center">Email</th>
                                            <th class="text-center">Phone</th>
                                            <th class="text-center">Company</th>
                                            <th class="text-center">Address</th>
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

            function loadUsers() {
                if (table) {
                    table.ajax.reload(null, false);
                } else {
                    table = $('#usersTable').DataTable({
                        processing: true,
                        serverSide: false,
                        ajax: {
                            url: "{{ route('admin.users.fetch') }}",
                            dataSrc: 'data'
                        },
                        columns: [
                            {
                                data: 'id',
                                width: '5%'
                            },
                            {
                                data: 'name',
                                width: '15%'
                            },
                            {
                                data: 'email',
                                width: '20%'
                            },
                            {
                                data: 'phone_no',
                                width: '15%'
                            },
                            {
                                data: 'company_name',
                                width: '20%'
                            },
                            {
                                data: 'address',
                                width: '25%'
                            }
                        ],
                        responsive: true,
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

            loadUsers();
        });
    </script>
@endsection