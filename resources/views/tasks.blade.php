@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    New Task
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
                    @include('common.errors')

                    <!-- New Task Form -->
                    <form action="/task" method="POST" class="form-horizontal" enctype="multipart/form-data">
                        {{ csrf_field() }}

                        <!-- Task Name -->
                        <div class="form-group">
                            <label for="task-name" class="col-sm-3 control-label">Task</label>

                            <div class="col-sm-6">
                                <input type="text" name="name" id="task-name" class="form-control" value="{{ old('task') }}">
                            </div>
                        </div>

                         <!-- Task File -->
                        <div class="form-group">
                            <label for="task-file" class="col-sm-3 control-label">File</label>

                            <div class="col-sm-6">
                                <input type="file" name="file" id="task-file" class="form-control">
                            </div>
                        </div>

                        <!-- Add Task Button -->
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-6">
                                <button type="submit" class="btn btn-default">
                                    <i class="fa fa-btn fa-plus"></i>Add Task
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Tasks -->
            @if (count($tasks) > 0)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Current Tasks
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped task-table">
                            <thead>
                                <th>Task</th>                             
                                <th>File</th>
                                <th>&nbsp;</th>
                                <th>&nbsp;</th>
                            </thead>
                            <tbody>
                                @forelse ($tasks as $task)
                                    <tr>
                                        <td class="table-text"><div>{{ $task->name }}</div></td>                                         
                                        <td class="table-text">
                                            <div>
                                                @if($task->file_path)
                                                    <button type="button" class="btn btn-info btn-sm preview-file" data-file="{{$task->file_path}}" data-file-url="{{ Storage::disk('azure')->temporaryUrl($task->file_path, now()->addMinutes(5)) }}">
                                                        Preview
                                                    </button>
                                                @else
                                                    No File
                                                @endif
                                            </div>
                                        </td>   
                                        <!-- Task Edit Button -->                 
                                        <td class="table-text">
                                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editTaskModal" 
                                                data-task-id="{{ $task->id }}" 
                                                data-task-name="{{ $task->name }}" 
                                                data-task-file="{{ $task->file_path }}">
                                                Edit
                                            </button>
                                        </td>                  
                                        <!-- Task Delete Button -->
                                        <td>
                                            <form action="{{'/task/' . $task->id }}" method="POST">
                                                {{ csrf_field() }}
                                                {{ method_field('DELETE') }}

                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fa fa-btn fa-trash"></i>Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4">No tasks available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            <!-- Elapsed time -->
            <div class="panel panel-default">
                <div class="panel-body">
                    Response time: {{ $elapsed * 1000 }} milliseconds.
                </div>
            </div>
            <!--Edit Modal-->
            <div class="modal fade" id="editTaskModal" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form id="editTaskForm" method="POST" enctype="multipart/form-data"  action="">
                            {{ csrf_field() }}
                            @method('PUT')
                            
                            <div class="modal-header">
                                <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="modal-body">
                                <input type="hidden" id="editTaskId" name="task_id">

                                <div class="form-group">
                                    <label for="task-name" class="col-form-label">Task Name:</label>
                                    <input type="text" class="form-control" id="editTaskName" name="name" required>
                                </div>

                                <div class="form-group">
                                    <label for="task-file" class="col-form-label">File:</label>
                                    <input type="file" class="form-control" id="editTaskFile" name="file">
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="filePreviewModal" tabindex="-1" role="dialog" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="filePreviewModalLabel">File Preview</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="filePreviewContainer"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>    
@endsection
@section('custom-script')
<script>    
    document.addEventListener('DOMContentLoaded', function () {
            const previewButtons = document.querySelectorAll('.preview-file');            
            previewButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const fileUrl = this.getAttribute('data-file-url');
                    const fileGetExt = this.getAttribute('data-file');
                    const fileExtension = fileGetExt.split('.').pop().toLowerCase();
                    const previewContainer = document.getElementById('filePreviewContainer');

             
                    previewContainer.innerHTML = '';

                    if (['jpg', 'jpeg', 'png'].includes(fileExtension)) {                       
                        previewContainer.innerHTML = `<img src="${fileUrl}" class="img-fluid" alt="File Preview">`;
                    } else if (fileExtension === 'pdf') {                     
                        previewContainer.innerHTML = `<iframe src="${fileUrl}" style="width:100%; height:500px;" frameborder="0"></iframe>`;
                    } else {                        
                        previewContainer.innerHTML = `<a href="${fileUrl}" target="_blank">Download file</a>`;
                    }
                    $('#filePreviewModal').modal('show');
                });
            });           
        });  
        
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $('#editTaskModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var taskId = button.data('task-id');
                var taskName = button.data('task-name');
                var taskFile = button.data('task-file');    
                console.log("EDIT MODAL");
                console.log({taskId, taskName, taskFile});            
                var modal = $(this);
                modal.find('#editTaskId').val(taskId);
                modal.find('#editTaskName').val(taskName);
                modal.find('#editTaskForm').attr('action', '/task/' + taskId);
        });

        $('#editTaskForm').on('submit', function (event) {
            event.preventDefault(); 
            console.log("SUBMIT UPDATE");
            console.log(this);
            console.log($(this).attr('action'));
            var formData = new FormData(this); 
            $.ajax({
                url: '{{ route('task.update') }}',
                type: 'PUT',
                data: formData, 
                processData: false,
                contentType: false, 
                success: function (response) {                  
                    $('#editTaskModal').modal('hide');                   
                    //location.reload();
                },
                error: function (xhr) {                  
                    console.error('Error:', xhr.responseText);
                }
            });
        });
     });
</script>
@endsection
