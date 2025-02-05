@extends('admin.layouts.admin_master')

@section('title', 'Expense')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="text">
                    <h4 class="card-title">Expense</h4>
                    <p class="card-text">List</p>
                </div>
                <div class="action_btn">
                    <!-- createModal -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel1">Create</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="#" method="POST" id="create_form">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">Expense Category Name</label>
                                            <select name="expense_category_id" class="form-select">
                                                <option value="">Select Expense Category</option>
                                                @foreach ($expense_categories as $expense_category)
                                                <option value="{{ $expense_category->id }}" {{ ($expense_category->expense_category_name == "Staff Salary" || $expense_category->expense_category_name == "Staff Bonus") ? 'disabled' : '' }} >{{ $expense_category->expense_category_name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger error-text expense_category_id_error"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Expense Date</label>
                                            <input type="date" name="expense_date" class="form-control" />
                                            <span class="text-danger error-text expense_date_error"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Expense Title</label>
                                            <input type="text" name="expense_title" class="form-control" placeholder="Enter Expense Title" />
                                            <span class="text-danger error-text expense_title_error"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Expense Cost</label>
                                            <input type="text" name="expense_cost" class="form-control" placeholder="Enter Expense Cost" />
                                            <span class="text-danger error-text expense_cost_error"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Expense Description</label>
                                            <textarea name="expense_description" class="form-control" placeholder="Enter Expense Description"></textarea>
                                            <span class="text-danger error-text expense_description_error"></span>
                                        </div>
                                        <button type="submit" id="create_btn" class="btn btn-primary">Create</button>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- trashedModal -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#trashedModal">
                        <i class="fa-solid fa-recycle"></i>
                    </button>
                    <div class="modal fade" id="trashedModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel1">Recycle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-primary" id="trashed_expense_table" style="width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Sl No</th>
                                                <th>Expense Title</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="filter">
                    <div class="row mb-3">
                        <div class="col-lg-3">
                            <label class="form-label">Expense Category Name</label>
                            <select class="form-select filter_data" id="filter_expense_category_id">
                                <option value="">All</option>
                                @foreach ($expense_categories as $expense_category)
                                <option value="{{ $expense_category->id }}">{{ $expense_category->expense_category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Expense Date Start</label>
                            <input type="date" class="form-control filter_data" id="filter_expense_date_start">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Expense Date End</label>
                            <input type="date" class="form-control filter_data" id="filter_expense_date_end">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-light" id="all_expense_table">
                        <thead>
                            <tr>
                                <th>Sl No</th>
                                <th>Expense Category</th>
                                <th>Expense Title</th>
                                <th>Expense Cose</th>
                                <th>Expense Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                            <!-- Modal -->
                            <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel1">Update</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="#" method="POST" id="edit_form">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="" id="expense_id">
                                                <div class="mb-3">
                                                    <label class="form-label">Expense Category Name</label>
                                                    <select name="expense_category_id" id="expense_category_id" class="form-select">
                                                        <option value="">Select Expense Category</option>
                                                        @foreach ($expense_categories as $expense_category)
                                                        <option value="{{ $expense_category->id }}" {{ ($expense_category->expense_category_name == "Staff Salary" || $expense_category->expense_category_name == "Staff Bonus") ? 'disabled' : '' }}>{{ $expense_category->expense_category_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="text-danger error-text update_expense_category_id_error"></span>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Expense Date</label>
                                                    <input type="date" name="expense_date" id="expense_date" class="form-control" />
                                                    <span class="text-danger error-text update_expense_date_error"></span>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Expense Title</label>
                                                    <input type="text" name="expense_title" id="expense_title" class="form-control" placeholder="Enter Expense Title" />
                                                    <span class="text-danger error-text update_expense_title_error"></span>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Expense Cost</label>
                                                    <input type="text" name="expense_cost" id="expense_cost" class="form-control" placeholder="Enter Expense Cost" />
                                                    <span class="text-danger error-text update_expense_cost_error"></span>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Expense Description</label>
                                                    <textarea name="expense_description" id="expense_description" class="form-control" placeholder="Enter Expense Description"></textarea>
                                                    <span class="text-danger error-text update_expense_description_error"></span>
                                                </div>
                                                <button type="submit" id="update_btn" class="btn btn-primary">Update</button>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Read Data
        table = $('#all_expense_table').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax: {
                url: "{{ route('expense.index') }}",
                "data":function(e){
                    e.expense_category_id = $('#filter_expense_category_id').val();
                    e.expense_date_start = $('#filter_expense_date_start').val();
                    e.expense_date_end = $('#filter_expense_date_end').val();
                },
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'expense_category_name', name: 'expense_category_name'},
                {data: 'expense_title', name: 'expense_title'},
                {data: 'expense_cost', name: 'expense_cost'},
                {data: 'expense_date', name: 'expense_date'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ],
        });

        // Filter Data
        $(document).on('change', '.filter_data', function(e){
            e.preventDefault();
            $('#all_expense_table').DataTable().ajax.reload()
        })

        // Store Data
        $('#create_form').on('submit', function(e){
            e.preventDefault();
            const form_data = new FormData(this);
            $("#create_btn").text('Creating...');
            $.ajax({
                url: '{{ route('expense.store') }}',
                method: 'POST',
                data: form_data,
                cache: false,
                contentType: false,
                processData: false,
                dataType: 'json',
                beforeSend:function(){
                    $(document).find('span.error-text').text('');
                },
                success: function(response) {
                    if (response.status == 400) {
                        $.each(response.error, function(prefix, val){
                            $('span.'+prefix+'_error').text(val[0]);
                        })
                    }else{
                        $("#create_btn").text('Created');
                        $("#create_form")[0].reset();
                        $('.btn-close').trigger('click');
                        table.ajax.reload();
                        toastr.success(response.message);
                    }
                }
            });
        });

        // Edit Form
        $(document).on('click', '.editBtn', function(e){
            e.preventDefault();
            var id = $(this).attr('id');
            var url = "{{ route('expense.edit', ":id") }}";
            url = url.replace(':id', id)
            $.ajax({
                url:  url,
                method: 'GET',
                success: function(response) {
                    $("#expense_category_id").val(response.expense_category_id);
                    $("#expense_date").val(response.expense_date);
                    $("#expense_title").val(response.expense_title);
                    $("#expense_cost").val(response.expense_cost);
                    $("#expense_description").val(response.expense_description);
                    $('#expense_id').val(response.id)
                }
            });
        })

        // Update Data
        $('#edit_form').on('submit', function(e){
            e.preventDefault();
            var id = $('#expense_id').val();
            var url = "{{ route('expense.update', ":id") }}";
            url = url.replace(':id', id)
            const form_data = new FormData(this);
            $("#update_btn").text('Updating...');
            $.ajax({
                url: url,
                method: 'POST',
                data: form_data,
                cache: false,
                contentType: false,
                processData: false,
                dataType: 'json',
                beforeSend:function(){
                    $(document).find('span.error-text').text('');
                },
                success: function(response) {
                    if (response.status == 400) {
                        $.each(response.error, function(prefix, val){
                            $('span.update_'+prefix+'_error').text(val[0]);
                        })
                    }else{
                        $("#update_btn").text('Updated');
                        $('.btn-close').trigger('click');
                        table.ajax.reload()
                        toastr.success(response.message);
                    }
                }
            });
        });

        // Delete Data
        $(document).on('click', '.deleteBtn', function(e){
            e.preventDefault();
            let id = $(this).attr('id');
            var url = "{{ route('expense.destroy', ":id") }}";
            url = url.replace(':id', id)
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: 'DELETE',
                        success: function(response) {
                            table.ajax.reload();
                            trashed_table.ajax.reload();
                            toastr.warning(response.message);
                        }
                    });
                }
            })
        })

        // Trashed Data
        trashed_table = $('#trashed_expense_table').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax: {
                url: "{{ route('expense.trashed') }}",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'expense_title', name: 'expense_title'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ],
        });

        // Restore Data
        $(document).on('click', '.restoreBtn', function(e){
            e.preventDefault();
            let id = $(this).attr('id');
            var url = "{{ route('expense.restore', ":id") }}";
            url = url.replace(':id', id)
            $.ajax({
                url: url,
                method: 'GET',
                success: function(response) {
                    table.ajax.reload();
                    trashed_table.ajax.reload();
                    $('.btn-close').trigger('click');
                    toastr.success(response.message);
                }
            });
        })

        // Force Delete
        $(document).on('click', '.forceDeleteBtn', function(e){
            e.preventDefault();
            $('.btn-close').trigger('click');
            let id = $(this).attr('id');
            var url = "{{ route('expense.forcedelete', ":id") }}";
            url = url.replace(':id', id)
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: 'GET',
                        success: function(response) {
                            trashed_table.ajax.reload();
                            $('.btn-close').trigger('click');
                            toastr.error(response.message);
                        }
                    });
                }
            })
        })
    });
</script>
@endsection
